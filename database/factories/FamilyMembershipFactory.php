<?php

declare(strict_types=1);

namespace Database\Factories;

use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FamilyMembership>
 */
class FamilyMembershipFactory extends Factory
{
    /** @var class-string<FamilyMembership> */
    protected $model = FamilyMembership::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'user_id' => User::factory(),
        ];
    }
}
