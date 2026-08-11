<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\RecipeStep;
use App\FamilyAccess\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;
use LogicException;

/** @extends Factory<RecipeStep> */
final class RecipeStepFactory extends Factory
{
    protected $model = RecipeStep::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'recipe_id' => fn (mixed $attributes): int => Recipe::factory()->create(['family_id' => $this->familyId($attributes)])->id,
            'position' => 1,
            'instruction' => fake()->sentence(),
        ];
    }

    private function familyId(mixed $attributes): int
    {
        $familyId = is_array($attributes) ? ($attributes['family_id'] ?? null) : null;
        if ( ! is_int($familyId)) {
            throw new LogicException('Recipe Step factories require a resolved Family.');
        }

        return $familyId;
    }
}
