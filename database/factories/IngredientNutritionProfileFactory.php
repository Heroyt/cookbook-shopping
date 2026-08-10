<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\IngredientNutritionProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<IngredientNutritionProfile> */
final class IngredientNutritionProfileFactory extends Factory
{
    /** @var class-string<IngredientNutritionProfile> */
    protected $model = IngredientNutritionProfile::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'ingredient_id' => Ingredient::factory(), 'basis_kind' => 'grams', 'basis_quantity' => 100,
            'energy_kcal' => 100, 'fat_grams' => 1, 'protein_grams' => 1, 'carbohydrate_grams' => 1,
        ];
    }
}
