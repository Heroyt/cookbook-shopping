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
            'document_version' => $changeSet->document_version,
            'client_request_id' => $changeSet->client_request_id,
            'digest' => $changeSet->digest,
            'credential' => ['id' => $changeSet->agent_credential_id, 'name' => $changeSet->credential_name],
            'issuer' => ['user_id' => $changeSet->issuer_user_id, 'name' => $changeSet->issuer_name],
            'title' => $changeSet->title,
            'source_urls' => $changeSet->source_urls,
            'note' => $changeSet->note,
            'supersedes_id' => $changeSet->supersedes_id,
            'resource_types' => $changeSet->resource_types,
            'outcome' => $changeSet->outcome,
            'operation_count' => $changeSet->operation_count,
            'payload_bytes' => $changeSet->payload_bytes,
            'expires_at' => $changeSet->expires_at->utc()->format('Y-m-d\TH:i:s\Z'),
            'created_at' => $changeSet->created_at?->utc()->format('Y-m-d\TH:i:s\Z'),
            'applied_at' => $changeSet->applied_at?->utc()->format('Y-m-d\TH:i:s\Z'),
            'terminal_at' => $changeSet->terminal_at?->utc()->format('Y-m-d\TH:i:s\Z'),
            'canonical_request' => $changeSet->canonical_request,
            'preview' => $changeSet->preview_document,
            'warning_acknowledgements' => $changeSet->warning_acknowledgements,
            'identifier_mappings' => $changeSet->identifier_mappings,
            'result' => $changeSet->result_document,
        ];
    }
}
