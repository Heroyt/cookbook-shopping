<?php

declare(strict_types=1);

namespace App\AgentIntegration;

use App\AgentIntegration\Values\AgentCredentialRestrictionResult;

final readonly class AgentCredentialRestrictionPresenter
{
    /** @return array<string, bool|int|string|null> */
    public function present(AgentCredentialRestrictionResult $result): array
    {
        $credential = $result->credential;

        return [
            'credential_id' => $credential->id,
            'action' => $result->action->value,
            'status' => $credential->revoked_at === null ? 'active' : 'revoked',
            'expires_at' => $credential->expires_at?->utc()->format('Y-m-d\TH:i:s\Z'),
            'revoked_at' => $credential->revoked_at?->utc()->format('Y-m-d\TH:i:s\Z'),
            'changed' => $result->changed,
        ];
    }
}
