<?php

declare(strict_types=1);

namespace App\MealPlanning\Http\Controllers;

use App\Cookbook\Models\Recipe;
use App\FamilyAccess\AuthorizedFamilyContext;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Http\Requests\AuthenticatedRequest;
use App\MealPlanning\Actions\CreateCalendarEntry;
use App\MealPlanning\Actions\DeleteCalendarEntry;
use App\MealPlanning\Actions\UpdateCalendarEntry;
use App\MealPlanning\Http\Requests\CalendarAlternativeStoreRequest;
use App\MealPlanning\Http\Requests\CalendarEntryDestroyRequest;
use App\MealPlanning\Http\Requests\CalendarEntryStoreRequest;
use App\MealPlanning\Http\Requests\CalendarEntryUpdateRequest;
use App\MealPlanning\Http\Requests\CalendarGenerateRequest;
use App\MealPlanning\Http\Requests\CalendarGenerationStateRequest;
use App\MealPlanning\Http\Requests\CalendarIndexRequest;
use App\MealPlanning\Presenters\ShoppingListPresenter;
use App\MealPlanning\Queries\BuildCurrentFamilyCalendarGenerationRequest;
use App\MealPlanning\Queries\CurrentFamilyCalendar;
use App\MealPlanning\Session\CalendarGenerationSession;
use App\MealPlanning\Values\CalendarEntryWriteResult;
use App\MealPlanning\Values\MealLabel;
use App\ShoppingGeneration\ShoppingListGenerator;
use App\ShoppingGeneration\Values\AlternativeChoice;
use App\ShoppingGeneration\Values\GenerationRequest;
use App\ShoppingGeneration\Values\GenerationResult;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

final readonly class CalendarController
{
    public function __construct(
        private CreateCalendarEntry $createEntry,
        private UpdateCalendarEntry $updateEntry,
        private DeleteCalendarEntry $deleteEntry,
        private CurrentFamilyScope $scope,
        private CurrentFamilyCalendar $currentFamilyCalendar,
        private CalendarGenerationSession $generationSession,
        private BuildCurrentFamilyCalendarGenerationRequest $generationRequest,
        private ShoppingListGenerator $shoppingListGenerator,
        private ShoppingListPresenter $shoppingListPresenter,
    ) {}

    public function index(CalendarIndexRequest $request): Response
    {
        $week = CarbonImmutable::parse($request->week() ?? 'today');
        $data = $this->scope->within(
            $request->authenticatedUser(),
            fn (Family $family): array => [
                ...$this->currentFamilyCalendar->project($family, $week),
                'selectedDates' => $this->generationSession->dates($request->session(), $family->id),
            ],
        );

        return Inertia::render('calendar/Index', $data);
    }

    public function store(CalendarEntryStoreRequest $request): RedirectResponse
    {
        $results = $this->scope->withinContext(
            $request->authenticatedUser(),
            fn (AuthorizedFamilyContext $context): array => DB::transaction(function () use ($context, $request): array {
                $results = [];
                $start = CarbonImmutable::parse($request->calendarDate());
                for ($offset = 0; $offset < $request->repeatDays(); $offset++) {
                    $results[] = $this->createEntry->handle(
                        $context,
                        $request->recipeId(),
                        $start->addDays($offset)->toDateString(),
                        $request->mealLabel(),
                        $request->servingCount(),
                    );
                }

                return $results;
            }),
        );
        $result = $results[0];
        if ($request->repeatDays() === 1) {
            $this->flashResult($result);
        } else {
            $created = count(array_filter($results, static fn (CalendarEntryWriteResult $item): bool => ! $item->merged));
            $merged = count($results) - $created;
            Inertia::flash('toast', [
                'type' => 'success',
                'message' => "Přidáno dnů: {$created}, sloučeno s existujícími: {$merged}.",
            ]);
        }

        return to_route('calendar.index', ['week' => $this->week($result)]);
    }

    public function update(CalendarEntryUpdateRequest $request, int $entry): RedirectResponse
    {
        $result = $this->scope->withinContext(
            $request->authenticatedUser(),
            fn (AuthorizedFamilyContext $context): CalendarEntryWriteResult => $this->updateEntry->handle(
                $context,
                $entry,
                $request->recipeId(),
                $request->calendarDate(),
                $request->mealLabel(),
                $request->servingCount(),
            ),
        );
        $this->flashResult($result);

        return to_route('calendar.index', ['week' => $this->week($result)]);
    }

    public function destroy(CalendarEntryDestroyRequest $request, int $entry): RedirectResponse
    {
        $this->scope->withinContext(
            $request->authenticatedUser(),
            function (AuthorizedFamilyContext $context) use ($entry): void {
                $this->deleteEntry->handle($context, $entry);
            },
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Calendar Entry deleted.')]);

        return to_route('calendar.index');
    }

    public function generate(CalendarGenerateRequest $request): RedirectResponse
    {
        $outcome = $this->scope->within($request->authenticatedUser(), function (Family $family) use ($request): array {
            $dates = $request->dates();
            $sameSelection = $this->generationSession->selectDates($request->session(), $family->id, $dates);
            $alternatives = $sameSelection
                ? $this->generationSession->alternatives($request->session(), $family->id)
                : [];
            $alternativesReset = false;
            try {
                $result = $this->regenerate($request, $family, $dates, $alternatives);
            } catch (InvalidArgumentException) {
                $alternatives = $this->validAlternatives($family, $dates, $alternatives);
                $result = $this->regenerate($request, $family, $dates, $alternatives);
                $this->generationSession->saveAlternatives($request->session(), $family->id, $alternatives);
                $alternativesReset = true;
            }

            return ['successful' => $result->isSuccessful(), 'alternativesReset' => $alternativesReset];
        });

        $toast = match ([$outcome['alternativesReset'], $outcome['successful']]) {
            [true, true] => ['type' => 'warning', 'message' => __('Unavailable Alternatives were reverted and the Calendar Shopping List was generated again.')],
            [true, false] => ['type' => 'error', 'message' => __('Unavailable Alternatives were reverted. The Calendar Shopping List still requires corrections.')],
            [false, true] => ['type' => 'success', 'message' => __('Calendar Shopping List generated.')],
            [false, false] => ['type' => 'error', 'message' => __('The Calendar Shopping List requires corrections.')],
        };
        Inertia::flash('toast', $toast);

        return to_route('calendar.generated');
    }

    public function generated(CalendarGenerationStateRequest $request): Response|RedirectResponse
    {
        $result = $this->scope->within($request->authenticatedUser(), function (Family $family) use ($request): ?array {
            $presentation = $this->generationSession->generated($request->session(), $family->id);
            if ($presentation === null) {
                return null;
            }

            return [...$presentation, 'selectedDates' => $this->generationSession->dates($request->session(), $family->id)];
        });

        return $result === null
            ? to_route('calendar.index')
            : Inertia::render('calendar/Generated', $result);
    }

    public function storeAlternative(CalendarAlternativeStoreRequest $request): RedirectResponse
    {
        $this->scope->within($request->authenticatedUser(), function (Family $family) use ($request): void {
            $dates = $this->generationSession->dates($request->session(), $family->id);
            $alternatives = $this->generationSession->alternatives($request->session(), $family->id);
            $alternatives[$request->originalIngredientId()] = $request->alternativeIngredientId();
            try {
                $this->regenerate($request, $family, $dates, $alternatives);
            } catch (InvalidArgumentException) {
                throw ValidationException::withMessages([
                    'alternative_ingredient_id' => __('The selected Alternative is no longer available for this Ingredient.'),
                ]);
            }
            $this->generationSession->saveAlternatives($request->session(), $family->id, $alternatives);
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Alternative applied.')]);

        return to_route('calendar.generated');
    }

    public function destroyAlternative(CalendarGenerationStateRequest $request, int $originalIngredient): RedirectResponse
    {
        $this->scope->within($request->authenticatedUser(), function (Family $family) use ($request, $originalIngredient): void {
            $dates = $this->generationSession->dates($request->session(), $family->id);
            $alternatives = $this->generationSession->alternatives($request->session(), $family->id);
            unset($alternatives[$originalIngredient]);
            try {
                $this->regenerate($request, $family, $dates, $alternatives);
            } catch (InvalidArgumentException) {
                throw ValidationException::withMessages([
                    'alternative_ingredient_id' => __('The selected Alternative is no longer available for this Ingredient.'),
                ]);
            }
            $this->generationSession->saveAlternatives($request->session(), $family->id, $alternatives);
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => __('The Alternative was reverted to the original Ingredient.')]);

        return to_route('calendar.generated');
    }

    private function flashResult(CalendarEntryWriteResult $result): void
    {
        $entry = $result->entry->loadMissing('recipe');
        $recipe = $entry->recipe;
        if ( ! $recipe instanceof Recipe) {
            abort(404);
        }
        $message = $result->merged ? 'Calendar Entry merged.' : 'Calendar Entry saved.';
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($message, [
                'recipe' => $recipe->name,
                'date' => CarbonImmutable::parse($entry->date)->format('j. n. Y'),
                'label' => MealLabel::displayForKey($entry->meal_label_key),
                'servings' => str_replace('.', ',', $this->normalized($entry->serving_count)),
            ]),
        ]);
    }

    private function week(CalendarEntryWriteResult $result): string
    {
        return CarbonImmutable::parse($result->entry->date)->startOfWeek()->toDateString();
    }

    private function normalized(string $decimal): string
    {
        return str_contains($decimal, '.') ? rtrim(rtrim($decimal, '0'), '.') : $decimal;
    }

    /**
     * @param  list<string>  $dates
     * @param  array<int, int>  $alternatives
     */
    private function regenerate(
        AuthenticatedRequest $request,
        Family $family,
        array $dates,
        array $alternatives,
    ): GenerationResult {
        $generationRequest = $this->generationRequest->handle($family, $dates, $alternatives);
        $result = $this->shoppingListGenerator->generate($generationRequest);
        $this->generationSession->saveDates($request->session(), $family->id, $dates);
        $this->generationSession->saveGenerated(
            $request->session(),
            $family->id,
            $this->shoppingListPresenter->present($result, $family),
        );

        return $result;
    }

    /**
     * @param  list<string>  $dates
     * @param  array<int, int>  $alternatives
     * @return array<int, int>
     */
    private function validAlternatives(Family $family, array $dates, array $alternatives): array
    {
        $baseRequest = $this->generationRequest->handle($family, $dates);
        $valid = [];
        foreach ($alternatives as $originalIngredientId => $alternativeIngredientId) {
            try {
                $this->shoppingListGenerator->generate(new GenerationRequest(
                    $baseRequest->selections,
                    [new AlternativeChoice($originalIngredientId, $alternativeIngredientId)],
                ));
                $valid[$originalIngredientId] = $alternativeIngredientId;
            } catch (InvalidArgumentException) {
                continue;
            }
        }

        return $valid;
    }
}
