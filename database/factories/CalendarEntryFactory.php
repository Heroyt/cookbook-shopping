<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Cookbook\Models\Recipe;
use App\FamilyAccess\Models\Family;
use App\MealPlanning\Models\CalendarEntry;
use Illuminate\Database\Eloquent\Factories\Factory;
use LogicException;

/** @extends Factory<CalendarEntry> */
final class CalendarEntryFactory extends Factory
{
    protected $model = CalendarEntry::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'recipe_id' => fn (mixed $attributes): int => Recipe::factory()->create([
                'family_id' => $this->familyId($attributes),
            ])->id,
            'date' => fake()->date(),
            'meal_label_key' => 'unlabeled',
            'serving_count' => '1',
        ];
    }

    private function familyId(mixed $attributes): int
    {
        $familyId = is_array($attributes) ? ($attributes['family_id'] ?? null) : null;
        if ( ! is_int($familyId)) {
            throw new LogicException('Calendar Entry factories require a resolved Family.');
        }

        return $familyId;
    }
}
