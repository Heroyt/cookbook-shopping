<?php

declare(strict_types=1);

namespace App\AgentIntegration\Actions;

use App\AgentIntegration\Models\AgentCredential;
use App\FamilyAccess\AuthorizedFamilyContext;

final readonly class RevokeAgentCredential
{
    public function __construct(private RecordAgentCredentialRevocation $recordRevocation) {}

    public function handle(
        AuthorizedFamilyContext $context,
        int $credentialId,
        string $reason = 'revoked',
    ): AgentCredential {
        $credential = AgentCredential::query()
            ->whereBelongsTo($context->family)
            ->findOrFail($credentialId);

        $this->recordRevocation->handle($credential, $context->user->id, $reason);

        return $credential;
    }
}
