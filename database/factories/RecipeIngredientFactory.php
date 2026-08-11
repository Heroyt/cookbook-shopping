<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\RecipeIngredient;
use App\FamilyAccess\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;
use LogicException;

/** @extends Factory<RecipeIngredient> */
final class RecipeIngredientFactory extends Factory
{
    protected $model = RecipeIngredient::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'recipe_id' => fn (mixed $attributes): int => Recipe::factory()->create(['family_id' => $this->familyId($attributes)])->id,
            'ingredient_id' => fn (mixed $attributes): int => Ingredient::factory()->create(['family_id' => $this->familyId($attributes)])->id,
            'position' => 1,
            'quantity' => 1,
            'quantity_kind' => 'grams',
        ];
    }

    private function familyId(mixed $attributes): int
    {
        $familyId = is_array($attributes) ? ($attributes['family_id'] ?? null) : null;
        if ( ! is_int($familyId)) {
            throw new LogicException('Recipe Ingredient factories require a resolved Family.');
        }

        return $familyId;
    }
}
