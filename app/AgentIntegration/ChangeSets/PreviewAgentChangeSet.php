<?php

declare(strict_types=1);

namespace App\AgentIntegration\ChangeSets;

use App\AgentIntegration\Exceptions\AgentApiException;
use App\AgentIntegration\Models\AgentChangeSet;
use App\AgentIntegration\Models\AgentCredential;
use App\AgentIntegration\Values\PreviewedAgentChangeSet;
use App\FamilyAccess\AuthorizedFamilyContext;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final readonly class PreviewAgentChangeSet
{
    public function __construct(
        private CanonicalAgentDocument $canonicalDocument,
        private AgentOperationPreviewer $operationPreviewer,
    ) {}

    /** @param array<string, mixed> $document */
    public function handle(
        AuthorizedFamilyContext $context,
        AgentCredential $credential,
        array $document,
        int $payloadBytes,
    ): PreviewedAgentChangeSet {
        $this->validateEnvelope($document, $payloadBytes);

        try {
            $canonicalRequest = $this->canonicalDocument->canonicalize($document);
        } catch (Throwable) {
            throw new AgentApiException(
                'validation_failed',
                'The Change Set contains a value that cannot be canonicalized.',
                422,
            );
        }

        $digest = $this->canonicalDocument->digest($canonicalRequest);
        $clientRequestId = $canonicalRequest['client_request_id'];

        if ( ! is_string($clientRequestId)) {
            throw new AgentApiException('validation_failed', 'The client_request_id is invalid.', 422, '/client_request_id');
        }

        return DB::transaction(function () use (
            $context,
            $credential,
            $canonicalRequest,
            $digest,
            $clientRequestId,
            $payloadBytes,
        ): PreviewedAgentChangeSet {
            $existing = AgentChangeSet::query()
                ->whereBelongsTo($credential, 'credential')
                ->where('client_request_id', $clientRequestId)
                ->lockForUpdate()
                ->first();

            if ($existing instanceof AgentChangeSet) {
                if ( ! hash_equals($existing->digest, $digest)) {
                    throw new AgentApiException(
                        'idempotency_conflict',
                        'The client_request_id was already used with a different canonical request.',
                        409,
                        '/client_request_id',
                        details: ['change_set_id' => $existing->id],
                    );
                }

                return new PreviewedAgentChangeSet($existing, false);
            }

            $preview = $this->operationPreviewer->preview($context, $credential, $canonicalRequest);
            $operations = $canonicalRequest['operations'];

            if ( ! is_array($operations)) {
                throw new AgentApiException('validation_failed', 'The operations field is invalid.', 422, '/operations');
            }

            $supersedesId = $canonicalRequest['supersedes_id'] ?? null;
            if ($supersedesId !== null) {
                if ( ! is_string($supersedesId) || ! AgentChangeSet::query()
                    ->whereBelongsTo($context->family)
                    ->whereKey($supersedesId)
                    ->exists()) {
                    throw new AgentApiException(
                        'family_scope_violation',
                        'The superseded Change Set is unavailable in this Family.',
                        404,
                        '/supersedes_id',
                    );
                }
            }

            $resourceTypes = collect($operations)
                ->map(static fn (mixed $operation): mixed => is_array($operation) ? ($operation['resource_type'] ?? null) : null)
                ->filter(static fn (mixed $resourceType): bool => is_string($resourceType))
                ->unique()
                ->sort()
                ->values()
                ->all();

            $changeSet = AgentChangeSet::query()->create([
                'id' => (string) Str::ulid(),
                'family_id' => $context->family->id,
                'agent_credential_id' => $credential->id,
                'issuer_user_id' => $credential->tokenable_id,
                'issuer_name' => $credential->issuer_name,
                'credential_name' => $credential->name,
                'client_request_id' => $clientRequestId,
                'status' => 'previewed',
                'digest' => $digest,
                'document_version' => 1,
                'canonical_request' => $canonicalRequest,
                'preview_document' => $preview,
                'resource_types' => $resourceTypes,
                'title' => $canonicalRequest['title'] ?? null,
                'source_urls' => $canonicalRequest['source_urls'] ?? [],
                'note' => $canonicalRequest['note'] ?? null,
                'supersedes_id' => $supersedesId,
                'payload_bytes' => $payloadBytes,
                'operation_count' => count($operations),
                'expires_at' => now()->addHours(Config::integer('agent-integration.change_sets.preview_expiry_hours')),
            ]);

            return new PreviewedAgentChangeSet($changeSet, true);
        });
    }

    /** @param array<string, mixed> $document */
    private function validateEnvelope(array $document, int $payloadBytes): void
    {
        $maxPayloadBytes = Config::integer('agent-integration.change_sets.max_payload_bytes');
        if ($payloadBytes > $maxPayloadBytes) {
            throw new AgentApiException(
                'payload_limit_exceeded',
                'The Change Set JSON payload exceeds the configured byte limit.',
                413,
                details: ['max_payload_bytes' => $maxPayloadBytes, 'payload_bytes' => $payloadBytes],
            );
        }

        if (($document['version'] ?? null) !== 1) {
            throw new AgentApiException(
                'validation_failed',
                'The Change Set version must be 1.',
                422,
                '/version',
            );
        }

        $clientRequestId = $document['client_request_id'] ?? null;
        if ( ! is_string($clientRequestId) || trim($clientRequestId) === '' || mb_strlen($clientRequestId) > 255) {
            throw new AgentApiException(
                'validation_failed',
                'The client_request_id must contain between 1 and 255 characters.',
                422,
                '/client_request_id',
            );
        }

        $operations = $document['operations'] ?? null;
        if ( ! is_array($operations) || ! array_is_list($operations) || $operations === []) {
            throw new AgentApiException(
                'validation_failed',
                'The Change Set must contain at least one operation.',
                422,
                '/operations',
            );
        }

        $maxOperations = Config::integer('agent-integration.change_sets.max_operations');
        if (count($operations) > $maxOperations) {
            throw new AgentApiException(
                'payload_limit_exceeded',
                'The Change Set exceeds the configured operation limit.',
                413,
                '/operations',
                details: ['max_operations' => $maxOperations, 'operation_count' => count($operations)],
            );
        }

        foreach (['title', 'note', 'supersedes_id'] as $optionalString) {
            if (array_key_exists($optionalString, $document)
                && $document[$optionalString] !== null
                && ! is_string($document[$optionalString])) {
                throw new AgentApiException(
                    'validation_failed',
                    "The {$optionalString} field must be a string or null.",
                    422,
                    "/{$optionalString}",
                );
            }
        }

        $sourceUrls = $document['source_urls'] ?? [];
        if ( ! is_array($sourceUrls) || ! array_is_list($sourceUrls)) {
            throw new AgentApiException(
                'validation_failed',
                'The source_urls field must be a JSON array.',
                422,
                '/source_urls',
            );
        }

        foreach ($sourceUrls as $index => $sourceUrl) {
            if ( ! is_string($sourceUrl) || filter_var($sourceUrl, FILTER_VALIDATE_URL) === false || mb_strlen($sourceUrl) > 2048) {
                throw new AgentApiException(
                    'validation_failed',
                    'Each source URL must be an absolute URL no longer than 2048 characters.',
                    422,
                    "/source_urls/{$index}",
                );
            }
        }
    }
}
