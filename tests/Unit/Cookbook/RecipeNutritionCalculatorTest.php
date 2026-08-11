<?php

declare(strict_types=1);

namespace Tests\Unit\Cookbook;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\IngredientNutritionProfile;
use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\RecipeIngredient;
use App\Cookbook\Services\RecipeNutritionCalculator;
use App\FamilyAccess\Models\Family;
use Tests\TestCase;

final class RecipeNutritionCalculatorTest extends TestCase
{
    public function test_it_calculates_exact_per_serving_nutrition_across_profile_bases(): void
    {
        $family = Family::factory()->create();
        $flour = Ingredient::factory()->for($family)->create(['name' => 'Mouka', 'weight_grams' => 500, 'piece_count' => null]);
        IngredientNutritionProfile::factory()->for($flour)->create([
            'basis_kind' => 'grams', 'basis_quantity' => 100, 'energy_kcal' => 200,
            'fat_grams' => 2, 'protein_grams' => 10, 'carbohydrate_grams' => 40,
        ]);
        $eggs = Ingredient::factory()->for($family)->create(['name' => 'Vejce', 'weight_grams' => null, 'piece_count' => 10]);
        IngredientNutritionProfile::factory()->for($eggs)->create([
            'basis_kind' => 'package', 'basis_quantity' => 1, 'energy_kcal' => 1000,
            'fat_grams' => 100, 'protein_grams' => 80, 'carbohydrate_grams' => 10,
        ]);
        $recipe = Recipe::factory()->for($family)->create(['base_servings' => 2]);
        RecipeIngredient::query()->create(['family_id' => $family->id, 'recipe_id' => $recipe->id, 'ingredient_id' => $flour->id, 'position' => 1, 'quantity' => 250, 'quantity_kind' => 'grams']);
        RecipeIngredient::query()->create(['family_id' => $family->id, 'recipe_id' => $recipe->id, 'ingredient_id' => $eggs->id, 'position' => 2, 'quantity' => 2, 'quantity_kind' => 'piece']);
        $recipe->load('ingredients.ingredient.nutritionProfile');

        $result = app(RecipeNutritionCalculator::class)->calculate($recipe);

        $this->assertSame('calculated', $result->status);
        $this->assertSame([
            'energyKcal' => '350.000000',
            'fatGrams' => '12.500000',
            'proteinGrams' => '20.500000',
            'carbohydrateGrams' => '51.000000',
        ], $result->perServing);
        $this->assertSame([], $result->missingIngredientNames);
    }

    public function test_complete_override_wins_and_missing_profiles_are_explicitly_incomplete(): void
    {
        $family = Family::factory()->create();
        $ingredient = Ingredient::factory()->for($family)->create(['name' => 'Tajemná surovina']);
        $recipe = Recipe::factory()->for($family)->create([
            'nutrition_energy_kcal' => 123, 'nutrition_fat_grams' => 4,
            'nutrition_protein_grams' => 5, 'nutrition_carbohydrate_grams' => 6,
        ]);
        RecipeIngredient::query()->create(['family_id' => $family->id, 'recipe_id' => $recipe->id, 'ingredient_id' => $ingredient->id, 'position' => 1, 'quantity' => 1, 'quantity_kind' => 'grams']);
        $recipe->load('ingredients.ingredient.nutritionProfile');

        $override = app(RecipeNutritionCalculator::class)->calculate($recipe);
        $this->assertSame('override', $override->status);
        $this->assertSame('123.000000', $override->perServing['energyKcal'] ?? null);

        $recipe->forceFill([
            'nutrition_energy_kcal' => null, 'nutrition_fat_grams' => null,
            'nutrition_protein_grams' => null, 'nutrition_carbohydrate_grams' => null,
        ]);
        $incomplete = app(RecipeNutritionCalculator::class)->calculate($recipe);
        $this->assertSame('incomplete', $incomplete->status);
        $this->assertSame([
            'energyKcal' => '0.000000',
            'fatGrams' => '0.000000',
            'proteinGrams' => '0.000000',
            'carbohydrateGrams' => '0.000000',
        ], $incomplete->perServing);
        $this->assertSame(['Tajemná surovina'], $incomplete->missingIngredientNames);
    }

    public function test_incomplete_nutrition_preserves_known_per_serving_totals(): void
    {
        $family = Family::factory()->create();
        $known = Ingredient::factory()->for($family)->create(['name' => 'Mouka', 'weight_grams' => '500']);
        IngredientNutritionProfile::factory()->for($known)->create([
            'basis_kind' => 'grams',
            'basis_quantity' => '100',
            'energy_kcal' => '200',
            'fat_grams' => '2',
            'protein_grams' => '10',
            'carbohydrate_grams' => '40',
        ]);
        $missing = Ingredient::factory()->for($family)->create(['name' => 'Tajemství', 'weight_grams' => '100']);
        $recipe = Recipe::factory()->for($family)->create(['base_servings' => '2']);
        RecipeIngredient::factory()->for($recipe)->for($known)->create([
            'family_id' => $family->id, 'position' => 1, 'quantity' => '100', 'quantity_kind' => 'grams',
        ]);
        RecipeIngredient::factory()->for($recipe)->for($missing)->create([
            'family_id' => $family->id, 'position' => 2, 'quantity' => '1', 'quantity_kind' => 'grams',
        ]);
        $recipe->load('ingredients.ingredient.nutritionProfile');

        $result = app(RecipeNutritionCalculator::class)->calculate($recipe);

        $this->assertSame('incomplete', $result->status);
        $this->assertSame([
            'energyKcal' => '100.000000',
            'fatGrams' => '1.000000',
            'proteinGrams' => '5.000000',
            'carbohydrateGrams' => '20.000000',
        ], $result->perServing);
        $this->assertSame(['Tajemství'], $result->missingIngredientNames);
    }
}
