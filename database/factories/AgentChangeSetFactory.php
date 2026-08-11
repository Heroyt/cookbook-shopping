<?php

declare(strict_types=1);

namespace Database\Factories;

use App\AgentIntegration\Models\AgentChangeSet;
use App\AgentIntegration\Models\AgentCredential;
use App\FamilyAccess\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use LogicException;

/** @extends Factory<AgentChangeSet> */
final class AgentChangeSetFactory extends Factory
{
    /** @var class-string<AgentChangeSet> */
    protected $model = AgentChangeSet::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'family_id' => Family::factory(),
            'agent_credential_id' => fn (mixed $attributes): int => AgentCredential::factory()->create([
                'family_id' => $this->familyId($attributes),
            ])->id,
            'issuer_user_id' => null,
            'issuer_name' => fake()->name(),
            'credential_name' => fake()->words(3, true),
            'client_request_id' => fake()->uuid(),
            'status' => 'previewed',
            'digest' => hash('sha256', fake()->uuid()),
            'document_version' => 1,
            'canonical_request' => ['version' => 1, 'operations' => []],
            'preview_document' => ['version' => 1, 'effects' => [], 'warnings' => []],
            'resource_types' => [],
            'source_urls' => [],
            'payload_bytes' => 2,
            'operation_count' => 0,
            'expires_at' => now()->addDay(),
        ];
    }

    private function familyId(mixed $attributes): int
    {
        $familyId = is_array($attributes) ? ($attributes['family_id'] ?? null) : null;

        if ( ! is_int($familyId)) {
            throw new LogicException('Agent Change Set factories require a resolved Family.');
        }

        return $familyId;
    }
}
