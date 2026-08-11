<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Cookbook\Models\RecipeTag;
use App\FamilyAccess\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RecipeTag> */
final class RecipeTagFactory extends Factory
{
    protected $model = RecipeTag::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['family_id' => Family::factory(), 'name' => fake()->unique()->word()];
    }
}
