<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Cookbook\Models\Store;
use App\FamilyAccess\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Store>
 */
final class StoreFactory extends Factory
{
    /** @var class-string<Store> */
    protected $model = Store::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'name' => fake()->unique()->company(),
        ];
    }
}
