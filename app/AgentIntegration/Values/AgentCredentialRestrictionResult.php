<?php

declare(strict_types=1);

namespace App\AgentIntegration\Values;

use App\AgentIntegration\AgentCredentialRestrictionAction;
use App\AgentIntegration\Models\AgentCredential;

final readonly class AgentCredentialRestrictionResult
{
    public function __construct(
        public AgentCredential $credential,
        public AgentCredentialRestrictionAction $action,
        public bool $changed,
    ) {}
}
