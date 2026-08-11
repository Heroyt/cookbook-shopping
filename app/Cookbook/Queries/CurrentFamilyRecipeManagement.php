<?php

declare(strict_types=1);

namespace App\Cookbook\Queries;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\RecipeIngredient;
use App\Cookbook\Models\RecipeStep;
use App\Cookbook\Models\RecipeTag;
use App\Cookbook\Services\EntityMediaStorage;
use App\Cookbook\Services\RecipeNutritionCalculator;
use App\Cookbook\Values\EntityMediaType;
use App\Cookbook\Values\NormalizedName;
use App\FamilyAccess\Models\Family;
use Illuminate\Database\Eloquent\Collection;

final class CurrentFamilyRecipeManagement
{
    public function __construct(
        private readonly RecipeNutritionCalculator $nutritionCalculator,
        private readonly EntityMediaStorage $entityMediaStorage,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Family $family, string $filter, string $search): array
    {
        $recipes = Recipe::query()
            ->whereBelongsTo($family)
            ->when($filter === 'active', fn ($query) => $query->whereNull('archived_at'))
            ->when($filter === 'archived', fn ($query) => $query->whereNotNull('archived_at'))
            ->with(['ingredients.ingredient.nutritionProfile', 'steps', 'tags'])
            ->orderBy('normalized_name')
            ->get();
        $searchKey = $search === '' ? '' : NormalizedName::from($search)->key;
        $projected = [];
        foreach ($recipes as $recipe) {
            $summary = $this->projectRecipe($family, $recipe, $searchKey);
            if ($searchKey === '' || $summary['matchReasons'] !== []) {
                $projected[] = $summary;
            }
        }
        usort($projected, static function (array $left, array $right): int {
            $leftLayer = is_int($left['matchLayer'] ?? null) ? $left['matchLayer'] : 3;
            $rightLayer = is_int($right['matchLayer'] ?? null) ? $right['matchLayer'] : 3;
            $leftName = is_string($left['name'] ?? null) ? $left['name'] : '';
            $rightName = is_string($right['name'] ?? null) ? $right['name'] : '';

            return ($leftLayer <=> $rightLayer) ?: strcmp($leftName, $rightName);
        });

        return [
            'recipes' => array_map(static function (array $recipe): array {
                unset($recipe['matchLayer']);

                return $recipe;
            }, $projected),
            'ingredients' => Ingredient::query()->whereBelongsTo($family)->whereNull('archived_at')->orderBy('normalized_name')->get()->map(fn (Ingredient $ingredient): array => [
                'id' => $ingredient->id, 'name' => $ingredient->name,
                'kinds' => array_values(array_filter([
                    $ingredient->weight_grams !== null ? 'grams' : null,
                    $ingredient->volume_millilitres !== null ? 'millilitres' : null,
                    $ingredient->piece_count !== null ? 'piece' : null,
                ])),
            ])->all(),
            'tags' => RecipeTag::query()->where('family_id', $family->id)->orderBy('normalized_name')->get(['id', 'name'])->map(fn (RecipeTag $tag): array => ['id' => $tag->id, 'name' => $tag->name])->all(),
            'filter' => $filter,
            'search' => $search,
        ];
    }

    /** @return array<string, mixed> */
    private function projectRecipe(Family $family, Recipe $recipe, string $searchKey): array
    {
        $ingredientLines = $recipe->getRelation('ingredients');
        $steps = $recipe->getRelation('steps');
        $tags = $recipe->getRelation('tags');
        $reasons = [];
        if ($searchKey !== '' && str_contains($recipe->normalized_name, $searchKey)) {
            $reasons[] = ['kind' => 'name', 'label' => __('Recipe name')];
        }
        $tagData = [];
        if ($tags instanceof Collection) {
            foreach ($tags as $tag) {
                if ( ! $tag instanceof RecipeTag) {
                    continue;
                }
                $tagData[] = ['id' => $tag->id, 'name' => $tag->name];
                if ($searchKey !== '' && str_contains($tag->normalized_name, $searchKey)) {
                    $reasons[] = ['kind' => 'tag', 'label' => __('Tag: :name', ['name' => $tag->name])];
                }
            }
        }
        $lineData = [];
        if ($ingredientLines instanceof Collection) {
            foreach ($ingredientLines as $line) {
                if ( ! $line instanceof RecipeIngredient) {
                    continue;
                }
                $ingredient = $line->getRelation('ingredient');
                if ( ! $ingredient instanceof Ingredient) {
                    continue;
                }
                $lineData[] = ['id' => $line->id, 'ingredientId' => $ingredient->id, 'ingredientName' => $ingredient->name, 'quantity' => $line->quantity, 'quantityKind' => $line->quantity_kind];
                if ($searchKey !== '' && str_contains($ingredient->normalized_name, $searchKey)) {
                    $reasons[] = ['kind' => 'ingredient', 'label' => __('Ingredient: :name', ['name' => $ingredient->name])];
                }
            }
        }
        $stepData = [];
        if ($steps instanceof Collection) {
            foreach ($steps as $step) {
                if ($step instanceof RecipeStep) {
                    $stepData[] = ['id' => $step->id, 'instruction' => $step->instruction];
                }
            }
        }
        $reasons = array_values(array_unique($reasons, SORT_REGULAR));
        $kinds = array_column($reasons, 'kind');
        $layer = in_array('name', $kinds, true) ? 0 : (in_array('tag', $kinds, true) ? 1 : (in_array('ingredient', $kinds, true) ? 2 : 3));

        $nutrition = $this->nutritionCalculator->calculate($recipe);

        return [
            'id' => $recipe->id, 'name' => $recipe->name, 'baseServings' => $recipe->base_servings, 'version' => $recipe->version,
            'coverUrl' => $this->entityMediaStorage->url($family, EntityMediaType::RecipeCover, $recipe->id),
            'sourceUrl' => $recipe->source_url, 'preparationMinutes' => $recipe->preparation_minutes, 'cookingMinutes' => $recipe->cooking_minutes,
            'notes' => $recipe->notes, 'archived' => $recipe->archived_at !== null,
            'ingredients' => $lineData, 'steps' => $stepData, 'tags' => $tagData, 'matchReasons' => $reasons, 'matchLayer' => $layer,
            'nutrition' => ['status' => $nutrition->status, 'perServing' => $nutrition->perServing, 'missingIngredientNames' => $nutrition->missingIngredientNames],
            'nutritionOverride' => $recipe->nutrition_energy_kcal === null ? null : [
                'energyKcal' => $recipe->nutrition_energy_kcal, 'fatGrams' => $recipe->nutrition_fat_grams,
                'proteinGrams' => $recipe->nutrition_protein_grams, 'carbohydrateGrams' => $recipe->nutrition_carbohydrate_grams,
            ],
        ];
    }
}
