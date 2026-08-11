<?php

declare(strict_types=1);

namespace App\AgentIntegration\Values;

use App\AgentIntegration\Models\AgentCredential;

final readonly class IssuedAgentCredential
{
    public function __construct(
        public AgentCredential $credential,
        public string $plainTextSecret,
    ) {}
}
