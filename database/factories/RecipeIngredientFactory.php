<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\RecipeIngredient;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RecipeIngredient> */
final class RecipeIngredientFactory extends Factory
{
    protected $model = RecipeIngredient::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['recipe_id' => Recipe::factory(), 'ingredient_id' => Ingredient::factory(), 'position' => 1, 'quantity' => 1, 'quantity_kind' => 'grams'];
    }
}
