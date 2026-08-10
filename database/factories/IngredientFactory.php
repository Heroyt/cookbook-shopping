<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Cookbook\Models\Ingredient;
use App\FamilyAccess\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Ingredient> */
final class IngredientFactory extends Factory
{
    /** @var class-string<Ingredient> */
    protected $model = Ingredient::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'name' => fake()->unique()->words(2, true),
            'weight_grams' => fake()->randomFloat(3, 1, 5000),
            'volume_millilitres' => null,
            'piece_count' => null,
        ];
    }
}
