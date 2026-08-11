<?php

declare(strict_types=1);

namespace App\AgentIntegration\Actions;

use App\AgentIntegration\AgentCredentialAbility;
use App\AgentIntegration\Models\AgentCredential;
use App\AgentIntegration\Values\IssuedAgentCredential;
use App\FamilyAccess\AuthorizedFamilyContext;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;

final readonly class IssueAgentCredential
{
    /**
     * @param  list<AgentCredentialAbility>  $abilities
     */
    public function handle(
        AuthorizedFamilyContext $context,
        string $name,
        array $abilities = [],
        ?DateTimeInterface $expiresAt = null,
    ): IssuedAgentCredential {
        $expiresAt = Carbon::instance($expiresAt ?? now()->addDays(
            Config::integer('agent-integration.credentials.default_expiry_days'),
        ));

        if ($expiresAt->isPast() || $expiresAt->greaterThan(now()->addDays(
            Config::integer('agent-integration.credentials.max_expiry_days'),
        ))) {
            throw new InvalidArgumentException('Agent credential expiry must be in the future and no more than one year away.');
        }

        $abilityValues = collect([
            AgentCredentialAbility::ContentRead,
            ...$abilities,
        ])->unique()->sortBy(
            fn (AgentCredentialAbility $ability): int => match ($ability) {
                AgentCredentialAbility::ContentRead => 0,
                AgentCredentialAbility::CookbookWrite => 1,
                AgentCredentialAbility::PlanningWrite => 2,
                AgentCredentialAbility::DestructiveWrite => 3,
            },
        )->map(
            fn (AgentCredentialAbility $ability): string => $ability->value,
        )->values()->all();

        $plainTextToken = $context->user->generateTokenString();

        $credential = $context->user->tokens()->create([
            'family_id' => $context->family->id,
            'issuer_name' => $context->user->name,
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
            'abilities' => $abilityValues,
            'expires_at' => $expiresAt,
        ]);

        if ( ! $credential instanceof AgentCredential) {
            throw new InvalidArgumentException('The configured Sanctum token model is not an Agent Credential.');
        }

        return new IssuedAgentCredential(
            credential: $credential,
            plainTextSecret: $credential->id . '|' . $plainTextToken,
        );
    }
}
