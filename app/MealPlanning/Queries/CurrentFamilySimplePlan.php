<?php

declare(strict_types=1);

namespace App\MealPlanning\Queries;

use App\Cookbook\Models\Recipe;
use App\FamilyAccess\Models\Family;
use App\MealPlanning\Values\SimplePlan;
use Illuminate\Validation\ValidationException;

final class CurrentFamilySimplePlan
{
    /** @return array{recipes: list<array{id: int, name: string}>, selections: list<array{recipeId: int, recipeName: string, servingCount: string, available: bool}>} */
    public function project(Family $family, SimplePlan $simplePlan): array
    {
        $recipes = array_values(Recipe::query()
            ->whereBelongsTo($family)
            ->whereNull('archived_at')
            ->orderBy('normalized_name')
            ->get(['id', 'name'])
            ->map(static fn (Recipe $recipe): array => ['id' => $recipe->id, 'name' => $recipe->name])
            ->all());

        $selectedRecipes = Recipe::query()
            ->whereBelongsTo($family)
            ->whereKey($simplePlan->recipeIds())
            ->orderBy('normalized_name')
            ->get(['id', 'name', 'archived_at']);
        $selections = [];
        foreach ($selectedRecipes as $recipe) {
            $servingCount = $simplePlan->servingCountFor($recipe->id);
            if ($servingCount === null) {
                continue;
            }
            $selections[] = [
                'recipeId' => $recipe->id,
                'recipeName' => $recipe->name,
                'servingCount' => $servingCount->toString(),
                'available' => $recipe->archived_at === null,
            ];
        }

        return ['recipes' => $recipes, 'selections' => $selections];
    }

    public function activeRecipe(Family $family, int $recipeId): Recipe
    {
        $recipe = Recipe::query()
            ->whereBelongsTo($family)
            ->whereKey($recipeId)
            ->whereNull('archived_at')
            ->first();

        if ( ! $recipe instanceof Recipe) {
            throw ValidationException::withMessages([
                'recipe_id' => __('The selected Recipe is unavailable in the Current Family.'),
            ]);
        }

        return $recipe;
    }

    public function recipe(Family $family, int $recipeId): Recipe
    {
        return Recipe::query()
            ->whereBelongsTo($family)
            ->whereKey($recipeId)
            ->firstOrFail();
    }
}
