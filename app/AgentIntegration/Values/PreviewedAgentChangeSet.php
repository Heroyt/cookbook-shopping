<?php

declare(strict_types=1);

namespace App\AgentIntegration\Values;

use App\AgentIntegration\Models\AgentChangeSet;

final readonly class PreviewedAgentChangeSet
{
    public function __construct(
        public AgentChangeSet $changeSet,
        public bool $created,
    ) {}
}
