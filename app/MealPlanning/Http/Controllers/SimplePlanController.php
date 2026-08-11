<?php

declare(strict_types=1);

namespace App\MealPlanning\Http\Controllers;

use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Http\Controllers\Controller;
use App\Http\Requests\AuthenticatedRequest;
use App\MealPlanning\Http\Requests\SimplePlanAlternativeStoreRequest;
use App\MealPlanning\Http\Requests\SimplePlanGenerateRequest;
use App\MealPlanning\Http\Requests\SimplePlanIndexRequest;
use App\MealPlanning\Http\Requests\SimplePlanSelectionStoreRequest;
use App\MealPlanning\Presenters\ShoppingListPresenter;
use App\MealPlanning\Queries\BuildCurrentFamilyGenerationRequest;
use App\MealPlanning\Queries\CurrentFamilySimplePlan;
use App\MealPlanning\Session\SimplePlanSession;
use App\MealPlanning\Values\ServingCount;
use App\MealPlanning\Values\SimplePlan;
use App\ShoppingGeneration\ShoppingListGenerator;
use App\ShoppingGeneration\Values\GenerationResult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

final class SimplePlanController extends Controller
{
    public function __construct(
        private readonly CurrentFamilyScope $scope,
        private readonly SimplePlanSession $simplePlanSession,
        private readonly CurrentFamilySimplePlan $currentFamilySimplePlan,
        private readonly BuildCurrentFamilyGenerationRequest $generationRequest,
        private readonly ShoppingListGenerator $shoppingListGenerator,
        private readonly ShoppingListPresenter $shoppingListPresenter,
    ) {}

    public function index(SimplePlanIndexRequest $request): Response
    {
        $data = $this->scope->within($request->authenticatedUser(), function (Family $family) use ($request): array {
            $simplePlan = $this->simplePlanSession->load($request->session(), $family->id);

            return $this->currentFamilySimplePlan->project($family, $simplePlan);
        });

        return Inertia::render('simple-plan/Index', $data);
    }

    public function store(SimplePlanSelectionStoreRequest $request): RedirectResponse
    {
        $result = $this->scope->within($request->authenticatedUser(), function (Family $family) use ($request): array {
            $recipe = $this->currentFamilySimplePlan->activeRecipe($family, $request->recipeId());
            $simplePlan = $this->simplePlanSession->load($request->session(), $family->id);

            try {
                $simplePlan = $simplePlan->add($recipe->id, ServingCount::from($request->servingCount()));
            } catch (InvalidArgumentException) {
                throw ValidationException::withMessages([
                    'serving_count' => __('The resulting Serving Count is outside the supported range.'),
                ]);
            }

            $this->simplePlanSession->save($request->session(), $family->id, $simplePlan);

            return [
                'recipeName' => $recipe->name,
                'servingCount' => $simplePlan->servingCountFor($recipe->id)?->toString() ?? '',
            ];
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Recipe :recipe is now in the Simple Plan for :servings servings in total.', [
                'recipe' => $result['recipeName'],
                'servings' => str_replace('.', ',', $result['servingCount']),
            ]),
        ]);

        return to_route('simple-plan.index');
    }

    public function generate(SimplePlanGenerateRequest $request): RedirectResponse
    {
        $successful = $this->scope->within($request->authenticatedUser(), function (Family $family) use ($request): bool {
            $simplePlan = $this->simplePlanSession->load($request->session(), $family->id);
            $alternatives = $this->simplePlanSession->alternatives($request->session(), $family->id);
            try {
                $result = $this->regenerate($request, $family, $simplePlan, $alternatives);
            } catch (InvalidArgumentException) {
                throw ValidationException::withMessages([
                    'plan' => __('One or more selected Alternatives are no longer available. Revert them to their original Ingredients and try again.'),
                ]);
            }

            return $result->isSuccessful();
        });

        Inertia::flash('toast', [
            'type' => $successful ? 'success' : 'error',
            'message' => $successful ? __('Shopping List generated.') : __('The Shopping List requires corrections.'),
        ]);

        return to_route('simple-plan.generated');
    }

    public function storeAlternative(SimplePlanAlternativeStoreRequest $request): RedirectResponse
    {
        $this->scope->within($request->authenticatedUser(), function (Family $family) use ($request): void {
            $simplePlan = $this->simplePlanSession->load($request->session(), $family->id);
            $alternatives = $this->simplePlanSession->alternatives($request->session(), $family->id);
            $alternatives[$request->originalIngredientId()] = $request->alternativeIngredientId();

            try {
                $this->regenerate($request, $family, $simplePlan, $alternatives);
            } catch (InvalidArgumentException) {
                throw ValidationException::withMessages([
                    'alternative_ingredient_id' => __('The selected Alternative is no longer available for this Ingredient.'),
                ]);
            }

            $this->simplePlanSession->saveAlternatives($request->session(), $family->id, $alternatives);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Alternative applied.')]);

        return to_route('simple-plan.generated');
    }

    public function destroyAlternative(SimplePlanGenerateRequest $request, int $originalIngredient): RedirectResponse
    {
        $this->scope->within($request->authenticatedUser(), function (Family $family) use ($request, $originalIngredient): void {
            $simplePlan = $this->simplePlanSession->load($request->session(), $family->id);
            $alternatives = $this->simplePlanSession->alternatives($request->session(), $family->id);
            unset($alternatives[$originalIngredient]);

            try {
                $this->regenerate($request, $family, $simplePlan, $alternatives);
            } catch (InvalidArgumentException) {
                throw ValidationException::withMessages([
                    'alternative_ingredient_id' => __('The selected Alternative is no longer available for this Ingredient.'),
                ]);
            }

            $this->simplePlanSession->saveAlternatives($request->session(), $family->id, $alternatives);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('The Alternative was reverted to the original Ingredient.'),
        ]);

        return to_route('simple-plan.generated');
    }

    public function generated(SimplePlanGenerateRequest $request): Response|RedirectResponse
    {
        $result = $this->scope->within($request->authenticatedUser(), fn (Family $family): ?array => $this->simplePlanSession->generated(
            $request->session(),
            $family->id,
        ));

        if ($result === null) {
            return to_route('simple-plan.index');
        }

        return Inertia::render('simple-plan/Generated', $result);
    }

    public function destroy(SimplePlanGenerateRequest $request, int $recipe): RedirectResponse
    {
        $recipeName = $this->scope->within($request->authenticatedUser(), function (Family $family) use ($request, $recipe): string {
            $selectedRecipe = $this->currentFamilySimplePlan->recipe($family, $recipe);
            $simplePlan = $this->simplePlanSession->load($request->session(), $family->id)->remove($selectedRecipe->id);
            $this->simplePlanSession->save($request->session(), $family->id, $simplePlan);

            return $selectedRecipe->name;
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Recipe :recipe was removed from the Simple Plan.', ['recipe' => $recipeName]),
        ]);

        return to_route('simple-plan.index');
    }

    /** @param array<int, int> $alternatives */
    private function regenerate(
        AuthenticatedRequest $request,
        Family $family,
        SimplePlan $simplePlan,
        array $alternatives,
    ): GenerationResult {
        $generationRequest = $this->generationRequest->handle($family, $simplePlan, $alternatives);
        $result = $this->shoppingListGenerator->generate($generationRequest);
        $this->simplePlanSession->saveGenerated(
            $request->session(),
            $family->id,
            $this->shoppingListPresenter->present($result),
        );

        return $result;
    }
}
