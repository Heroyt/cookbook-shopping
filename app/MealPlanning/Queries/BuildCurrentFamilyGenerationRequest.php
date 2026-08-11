<?php

declare(strict_types=1);

namespace App\MealPlanning\Queries;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\RecipeIngredient;
use App\Cookbook\Models\Store;
use App\Cookbook\Models\StoreSection;
use App\FamilyAccess\Models\Family;
use App\MealPlanning\Values\SimplePlan;
use App\ShoppingGeneration\Values\AlternativeIngredientDefinition;
use App\ShoppingGeneration\Values\GenerationRequest;
use App\ShoppingGeneration\Values\IngredientDefinition;
use App\ShoppingGeneration\Values\IngredientPackage;
use App\ShoppingGeneration\Values\IngredientPlacement;
use App\ShoppingGeneration\Values\QuantityKind;
use App\ShoppingGeneration\Values\RecipeIngredientInput;
use App\ShoppingGeneration\Values\RecipeSelection;
use App\ShoppingGeneration\Values\StoreReference;
use App\ShoppingGeneration\Values\StoreSectionReference;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class BuildCurrentFamilyGenerationRequest
{
    public function handle(Family $family, SimplePlan $simplePlan): GenerationRequest
    {
        if ($simplePlan->isEmpty()) {
            throw ValidationException::withMessages(['plan' => __('Add at least one Recipe to the Simple Plan.')]);
        }

        $recipes = Recipe::query()
            ->whereBelongsTo($family)
            ->whereKey($simplePlan->recipeIds())
            ->whereNull('archived_at')
            ->with('ingredients')
            ->get()
            ->keyBy('id');
        if ($recipes->count() !== count($simplePlan->recipeIds())) {
            throw ValidationException::withMessages([
                'plan' => __('The Simple Plan contains a Recipe that is no longer available in the Current Family.'),
            ]);
        }

        /** @var array<int, list<RecipeIngredient>> $linesByRecipe */
        $linesByRecipe = [];
        $originalIngredientIds = [];
        foreach ($recipes as $recipe) {
            $relation = $recipe->getRelation('ingredients');
            $lines = [];
            if ($relation instanceof Collection) {
                foreach ($relation as $line) {
                    if ($line instanceof RecipeIngredient) {
                        $lines[] = $line;
                        $originalIngredientIds[] = $line->ingredient_id;
                    }
                }
            }
            if ($lines === []) {
                throw ValidationException::withMessages([
                    'plan' => __('The Simple Plan contains a Recipe without Ingredients. Update the Recipe and try again.'),
                ]);
            }
            $linesByRecipe[$recipe->id] = $lines;
        }
        $originalIngredientIds = array_values(array_unique($originalIngredientIds));
        $alternativeIds = $this->alternativeIds($family, $originalIngredientIds);
        $allIngredientIds = $originalIngredientIds;
        foreach ($alternativeIds as $directAlternativeIds) {
            array_push($allIngredientIds, ...$directAlternativeIds);
        }
        $allIngredientIds = array_values(array_unique($allIngredientIds));
        $ingredients = Ingredient::query()
            ->whereBelongsTo($family)
            ->whereKey($allIngredientIds)
            ->with(['store', 'storeSection'])
            ->get()
            ->keyBy('id');
        $sectionPositions = $this->sectionPositions($ingredients);

        $selections = [];
        foreach ($simplePlan->recipeIds() as $recipeId) {
            $recipe = $recipes->get($recipeId);
            $servingCount = $simplePlan->servingCountFor($recipeId);
            if ( ! $recipe instanceof Recipe || $servingCount === null) {
                continue;
            }
            $inputs = [];
            foreach ($linesByRecipe[$recipe->id] ?? [] as $line) {
                $ingredient = $ingredients->get($line->ingredient_id);
                $quantityKind = QuantityKind::tryFrom($line->quantity_kind);
                if ( ! $ingredient instanceof Ingredient || $quantityKind === null) {
                    throw ValidationException::withMessages([
                        'plan' => __('The Simple Plan contains an unavailable Ingredient. Update the Recipe and try again.'),
                    ]);
                }
                $inputs[] = new RecipeIngredientInput(
                    ingredient: $this->ingredientDefinition(
                        $ingredient,
                        $ingredients,
                        $alternativeIds[$ingredient->id] ?? [],
                        $sectionPositions,
                    ),
                    quantity: $line->quantity,
                    quantityKind: $quantityKind,
                );
            }
            $selections[] = new RecipeSelection(
                recipeId: $recipe->id,
                recipeName: $recipe->name,
                baseServings: $recipe->base_servings,
                requestedServings: $servingCount->toString(),
                ingredients: $inputs,
            );
        }

        return new GenerationRequest($selections);
    }

    /**
     * @param  list<int>  $ingredientIds
     * @return array<int, list<int>>
     */
    private function alternativeIds(Family $family, array $ingredientIds): array
    {
        $alternatives = array_fill_keys($ingredientIds, []);
        if ($ingredientIds === []) {
            return $alternatives;
        }

        $edges = DB::table('ingredient_alternatives')
            ->where('family_id', $family->id)
            ->where(function (Builder $query) use ($ingredientIds): void {
                $query->whereIn('lower_ingredient_id', $ingredientIds)
                    ->orWhereIn('higher_ingredient_id', $ingredientIds);
            })
            ->get(['lower_ingredient_id', 'higher_ingredient_id']);
        foreach ($edges as $edge) {
            if ( ! is_numeric($edge->lower_ingredient_id) || ! is_numeric($edge->higher_ingredient_id)) {
                continue;
            }
            $lower = (int) $edge->lower_ingredient_id;
            $higher = (int) $edge->higher_ingredient_id;
            if (isset($alternatives[$lower])) {
                $alternatives[$lower][] = $higher;
            }
            if (isset($alternatives[$higher])) {
                $alternatives[$higher][] = $lower;
            }
        }

        return $alternatives;
    }

    /**
     * @param  Collection<int, Ingredient>  $ingredients
     * @return array<string, int>
     */
    private function sectionPositions(Collection $ingredients): array
    {
        $storeIds = [];
        $sectionIds = [];
        foreach ($ingredients as $ingredient) {
            if ($ingredient->store_id !== null) {
                $storeIds[] = $ingredient->store_id;
            }
            if ($ingredient->store_section_id !== null) {
                $sectionIds[] = $ingredient->store_section_id;
            }
        }
        $storeIds = array_values(array_unique($storeIds));
        $sectionIds = array_values(array_unique($sectionIds));
        if ($storeIds === [] || $sectionIds === []) {
            return [];
        }

        $positions = [];
        foreach (DB::table('store_store_section')->whereIn('store_id', $storeIds)->whereIn('store_section_id', $sectionIds)->get() as $pivot) {
            if ( ! is_numeric($pivot->store_id) || ! is_numeric($pivot->store_section_id) || ! is_numeric($pivot->position)) {
                continue;
            }
            $storeId = (int) $pivot->store_id;
            $sectionId = (int) $pivot->store_section_id;
            $positions["{$storeId}:{$sectionId}"] = (int) $pivot->position;
        }

        return $positions;
    }

    /**
     * @param  Collection<int, Ingredient>  $ingredients
     * @param  list<int>  $alternativeIds
     * @param  array<string, int>  $sectionPositions
     */
    private function ingredientDefinition(
        Ingredient $ingredient,
        Collection $ingredients,
        array $alternativeIds,
        array $sectionPositions,
    ): IngredientDefinition {
        $alternatives = [];
        foreach ($alternativeIds as $alternativeId) {
            $alternative = $ingredients->get($alternativeId);
            if ( ! $alternative instanceof Ingredient) {
                continue;
            }
            $alternatives[] = new AlternativeIngredientDefinition(
                id: $alternative->id,
                name: $alternative->name,
                normalizedName: $alternative->normalized_name,
                package: $this->package($alternative),
                active: $alternative->archived_at === null,
                placement: $this->placement($alternative, $sectionPositions),
            );
        }

        return new IngredientDefinition(
            id: $ingredient->id,
            name: $ingredient->name,
            normalizedName: $ingredient->normalized_name,
            package: $this->package($ingredient),
            placement: $this->placement($ingredient, $sectionPositions),
            alternatives: $alternatives,
        );
    }

    private function package(Ingredient $ingredient): IngredientPackage
    {
        return new IngredientPackage(
            weightGrams: $ingredient->weight_grams,
            volumeMillilitres: $ingredient->volume_millilitres,
            pieceCount: $ingredient->piece_count,
        );
    }

    /** @param array<string, int> $sectionPositions */
    private function placement(Ingredient $ingredient, array $sectionPositions): ?IngredientPlacement
    {
        $store = $ingredient->getRelation('store');
        if ( ! $store instanceof Store) {
            return null;
        }
        $storeReference = new StoreReference($store->id, $store->name, $store->normalized_name);
        $section = $ingredient->getRelation('storeSection');
        if ( ! $section instanceof StoreSection) {
            return new IngredientPlacement($storeReference);
        }
        $position = $sectionPositions["{$store->id}:{$section->id}"] ?? null;
        if ($position === null) {
            return new IngredientPlacement($storeReference);
        }

        return new IngredientPlacement(
            $storeReference,
            new StoreSectionReference($section->id, $section->name, $position),
        );
    }
}
