<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Cookbook\Models\StoreSection;
use App\Cookbook\Values\StoreSectionIcon;
use App\FamilyAccess\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StoreSection> */
final class StoreSectionFactory extends Factory
{
    /** @var class-string<StoreSection> */
    protected $model = StoreSection::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'name' => fake()->unique()->words(2, true),
            'colour' => fake()->hexColor(),
            'icon' => StoreSectionIcon::Package,
        ];
    }
}
