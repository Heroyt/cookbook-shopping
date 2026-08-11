<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Cookbook\Models\Recipe;
use App\FamilyAccess\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Recipe> */
final class RecipeFactory extends Factory
{
    protected $model = Recipe::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['family_id' => Family::factory(), 'name' => fake()->unique()->words(2, true), 'base_servings' => 4, 'version' => 1];
    }
}
