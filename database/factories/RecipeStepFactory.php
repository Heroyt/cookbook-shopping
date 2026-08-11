<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\RecipeStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RecipeStep> */
final class RecipeStepFactory extends Factory
{
    protected $model = RecipeStep::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['recipe_id' => Recipe::factory(), 'position' => 1, 'instruction' => fake()->sentence()];
    }
}
