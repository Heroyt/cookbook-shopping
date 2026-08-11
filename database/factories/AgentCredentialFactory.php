<?php

declare(strict_types=1);

namespace Database\Factories;

use App\AgentIntegration\AgentCredentialAbility;
use App\AgentIntegration\Models\AgentCredential;
use App\FamilyAccess\Models\Family;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AgentCredential>
 */
final class AgentCredentialFactory extends Factory
{
    /** @var class-string<AgentCredential> */
    protected $model = AgentCredential::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tokenable_type' => User::class,
            'tokenable_id' => User::factory(),
            'family_id' => Family::factory(),
            'issuer_name' => fake()->name(),
            'name' => fake()->words(3, true),
            'token' => hash('sha256', Str::random(48)),
            'abilities' => [AgentCredentialAbility::ContentRead->value],
            'expires_at' => now()->addDays(90),
        ];
    }
}
