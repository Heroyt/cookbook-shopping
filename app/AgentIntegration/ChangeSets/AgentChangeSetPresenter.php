<?php

declare(strict_types=1);

namespace App\AgentIntegration\ChangeSets;

use App\AgentIntegration\Models\AgentChangeSet;

final readonly class AgentChangeSetPresenter
{
    /** @return array<string, mixed> */
    public function present(AgentChangeSet $changeSet): array
    {
        return [
            'id' => $changeSet->id,
            'status' => $changeSet->status,
            'digest' => $changeSet->digest,
            'expires_at' => $changeSet->expires_at->utc()->format('Y-m-d\TH:i:s\Z'),
            'created_at' => $changeSet->created_at?->utc()->format('Y-m-d\TH:i:s\Z'),
            'applied_at' => $changeSet->applied_at?->utc()->format('Y-m-d\TH:i:s\Z'),
            'canonical_request' => $changeSet->canonical_request,
            'preview' => $changeSet->preview_document,
            'identifier_mappings' => $changeSet->identifier_mappings,
            'result' => $changeSet->result_document,
        ];
    }
}
