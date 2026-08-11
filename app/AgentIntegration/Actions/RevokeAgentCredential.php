<?php

declare(strict_types=1);

namespace App\AgentIntegration\Actions;

use App\AgentIntegration\ChangeSets\InvalidateCredentialPreviews;
use App\AgentIntegration\Models\AgentCredential;
use App\FamilyAccess\AuthorizedFamilyContext;

final readonly class RevokeAgentCredential
{
    public function __construct(private InvalidateCredentialPreviews $invalidateCredentialPreviews) {}

    public function handle(
        AuthorizedFamilyContext $context,
        int $credentialId,
        string $reason = 'revoked',
    ): AgentCredential {
        $credential = AgentCredential::query()
            ->whereBelongsTo($context->family)
            ->findOrFail($credentialId);

        if ($credential->revoked_at !== null) {
            return $credential;
        }

        $credential->forceFill([
            'revoked_at' => now(),
            'revoked_by_user_id' => $context->user->id,
            'revocation_reason' => $reason,
        ])->save();
        $this->invalidateCredentialPreviews->handle($credential);

        return $credential;
    }
}
