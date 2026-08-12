<?php

declare(strict_types=1);

namespace App\AgentIntegration\Actions;

use App\AgentIntegration\ChangeSets\InvalidateCredentialPreviews;
use App\AgentIntegration\Models\AgentCredential;

final readonly class RecordAgentCredentialRevocation
{
    public function __construct(private InvalidateCredentialPreviews $invalidateCredentialPreviews) {}

    public function handle(
        AgentCredential $credential,
        ?int $revokedByUserId,
        string $reason,
    ): bool {
        if ($credential->revoked_at !== null) {
            return false;
        }

        $credential->forceFill([
            'revoked_at' => now(),
            'revoked_by_user_id' => $revokedByUserId,
            'revocation_reason' => $reason,
        ])->save();
        $this->invalidateCredentialPreviews->handle($credential);

        return true;
    }
}
