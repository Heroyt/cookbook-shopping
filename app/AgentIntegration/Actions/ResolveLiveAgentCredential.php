<?php

declare(strict_types=1);

namespace App\AgentIntegration\Actions;

use App\AgentIntegration\Exceptions\InvalidAgentCredentialAuthority;
use App\AgentIntegration\Models\AgentCredential;
use App\FamilyAccess\AuthorizedFamilyContext;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;

final readonly class ResolveLiveAgentCredential
{
    public function handle(
        AuthorizedFamilyContext $context,
        AgentCredential $requestCredential,
    ): AgentCredential {
        $credential = AgentCredential::query()
            ->whereKey($requestCredential->id)
            ->whereBelongsTo($context->family)
            ->lockForUpdate()
            ->first();
        $membershipExists = FamilyMembership::query()
            ->whereBelongsTo($context->family)
            ->whereBelongsTo($context->user)
            ->lockForUpdate()
            ->exists();

        if ( ! $credential instanceof AgentCredential
            || $credential->revoked_at !== null
            || $credential->expires_at === null
            || $credential->expires_at->isPast()
            || $credential->tokenable_type !== User::class
            || $credential->tokenable_id !== $context->user->id
            || ! hash_equals($credential->token, $requestCredential->token)
            || ! $membershipExists) {
            throw new InvalidAgentCredentialAuthority();
        }

        return $credential;
    }
}
