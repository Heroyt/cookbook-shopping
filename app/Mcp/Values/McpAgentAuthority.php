<?php

declare(strict_types=1);

namespace App\Mcp\Values;

use App\AgentIntegration\Models\AgentCredential;
use App\FamilyAccess\AuthorizedFamilyContext;
use App\Mcp\Models\McpAuthorization;

final readonly class McpAgentAuthority
{
    public function __construct(
        public AuthorizedFamilyContext $context,
        public AgentCredential $credential,
        public McpAuthorization $authorization,
    ) {}
}
