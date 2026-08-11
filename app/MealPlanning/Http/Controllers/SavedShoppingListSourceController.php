<?php

declare(strict_types=1);

namespace App\MealPlanning\Http\Controllers;

use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\MealPlanning\Session\CalendarGenerationSession;
use App\MealPlanning\Session\SimplePlanSession;
use App\ShoppingGeneration\Actions\CreateSavedShoppingList;
use App\ShoppingGeneration\Http\Requests\SavedShoppingListRequest;
use App\ShoppingGeneration\Snapshots\SavedShoppingListPayload;
use App\ShoppingGeneration\Values\SavedShoppingListSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use InvalidArgumentException;

final readonly class SavedShoppingListSourceController
{
    public function __construct(
        private CurrentFamilyScope $scope,
        private SimplePlanSession $simplePlanSession,
        private CalendarGenerationSession $calendarGenerationSession,
        private SavedShoppingListPayload $payload,
        private CreateSavedShoppingList $save,
    ) {}

    public function storeSimplePlan(SavedShoppingListRequest $request): RedirectResponse
    {
        $this->scope->within($request->authenticatedUser(), function (Family $family) use ($request): void {
            $presentation = $this->simplePlanSession->generated($request->session(), $family->id);
            if ($presentation === null) {
                throw ValidationException::withMessages(['snapshot' => __('First generate a complete Shopping List.')]);
            }

            try {
                $payload = $this->payload->forSimplePlan(
                    $presentation,
                    $this->simplePlanSession->load($request->session(), $family->id)->toArray(),
                );
            } catch (InvalidArgumentException $exception) {
                throw ValidationException::withMessages([
                    'snapshot' => $this->saveError($exception),
                ]);
            }
            $this->save->handle($family, SavedShoppingListSource::SimplePlan, $payload);
        });

        $this->flashSaved();

        return back();
    }

    public function storeCalendar(SavedShoppingListRequest $request): RedirectResponse
    {
        $this->scope->within($request->authenticatedUser(), function (Family $family) use ($request): void {
            $presentation = $this->calendarGenerationSession->generated($request->session(), $family->id);
            if ($presentation === null) {
                throw ValidationException::withMessages(['snapshot' => __('First generate a complete Shopping List.')]);
            }

            try {
                $payload = $this->payload->forCalendar(
                    $presentation,
                    $this->calendarGenerationSession->dates($request->session(), $family->id),
                );
            } catch (InvalidArgumentException $exception) {
                throw ValidationException::withMessages([
                    'snapshot' => $this->saveError($exception),
                ]);
            }
            $this->save->handle($family, SavedShoppingListSource::Calendar, $payload);
        });

        $this->flashSaved();

        return back();
    }

    private function flashSaved(): void
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Shopping List saved to history.')]);
    }

    private function saveError(InvalidArgumentException $exception): string
    {
        return $exception->getMessage() === 'The Shopping List has Calculation Problems.'
            ? __('A Shopping List with problems cannot be saved.')
            : __('First generate a complete Shopping List.');
    }
}
