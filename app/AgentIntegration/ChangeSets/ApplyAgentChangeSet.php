<?php

declare(strict_types=1);

namespace App\AgentIntegration\ChangeSets;

use App\AgentIntegration\Actions\ResolveLiveAgentCredential;
use App\AgentIntegration\Exceptions\AgentApiException;
use App\AgentIntegration\Exceptions\InvalidAgentCredentialAuthority;
use App\AgentIntegration\Models\AgentChangeSet;
use App\AgentIntegration\Models\AgentCredential;
use App\FamilyAccess\AuthorizedFamilyContext;
use Illuminate\Support\Facades\DB;
use LogicException;

final readonly class ApplyAgentChangeSet
{
    public function __construct(
        private CanonicalAgentDocument $canonicalDocument,
        private AgentOperationPreviewer $operationPreviewer,
        private AgentOperationApplier $operationApplier,
        private ResolveLiveAgentCredential $liveCredential,
    ) {}

    /** @param list<string> $warningAcknowledgements */
    public function handle(
        AuthorizedFamilyContext $context,
        AgentCredential $credential,
        string $changeSetId,
        string $digest,
        array $warningAcknowledgements,
    ): AgentChangeSet {
        try {
            return DB::transaction(function () use ($context, $credential, $changeSetId, $digest, $warningAcknowledgements): AgentChangeSet {
                try {
                    $liveCredential = $this->liveCredential->handle($context, $credential);
                } catch (InvalidAgentCredentialAuthority) {
                    throw new AgentApiException(
                        'credential_invalidated',
                        'The Agent Credential no longer has live authority for this Family.',
                        401,
                    );
                }
                $changeSet = AgentChangeSet::query()
                    ->whereBelongsTo($context->family)
                    ->whereBelongsTo($liveCredential, 'credential')
                    ->whereKey($changeSetId)
                    ->lockForUpdate()
                    ->first();
                if ( ! $changeSet instanceof AgentChangeSet) {
                    throw new AgentApiException('family_scope_violation', 'The Change Set is unavailable to this Agent Credential.', 404);
                }
                $this->assertDigest($changeSet, $digest);
                $this->assertAcknowledgements($changeSet, $warningAcknowledgements);

                if ($changeSet->status === 'applied') {
                    return $changeSet;
                }
                if ($changeSet->status !== 'previewed') {
                    throw new AgentApiException('change_set_unavailable', 'The Change Set is no longer previewed.', 409, details: ['status' => $changeSet->status]);
                }
                if ($changeSet->expires_at->isPast()) {
                    throw new AgentApiException('preview_expired', 'The Change Set preview has expired.', 409, details: ['expires_at' => $changeSet->expires_at->utc()->format('Y-m-d\TH:i:s\Z')]);
                }

                $canonicalRequest = $changeSet->canonical_request;
                if ( ! hash_equals($changeSet->digest, $this->canonicalDocument->digest($canonicalRequest))) {
                    throw new AgentApiException('digest_mismatch', 'The stored canonical request no longer matches its digest.', 409);
                }
                $livePreview = $this->operationPreviewer->preview($context, $liveCredential, $canonicalRequest);
                if ($livePreview !== $changeSet->preview_document) {
                    throw new AgentApiException('stale_preview', 'The live preview no longer matches the stored preview.', 409);
                }
                $executionOrder = $livePreview['execution_order'];
                $result = $this->operationApplier->apply($context, $canonicalRequest, $executionOrder);
                $now = now();
                $changeSet->forceFill([
                    'status' => 'applied',
                    'outcome' => 'applied',
                    'warning_acknowledgements' => $warningAcknowledgements,
                    'identifier_mappings' => $result['identifier_mappings'],
                    'result_document' => $result['result'],
                    'applied_at' => $now,
                    'terminal_at' => $now,
                ])->save();

                return $changeSet->refresh();
            }, 3);
        } catch (AgentApiException $exception) {
            $terminalStatus = match ($exception->errorCode) {
                'preview_expired' => 'expired',
                'stale_preview' => 'stale',
                default => null,
            };
            if ($terminalStatus !== null) {
                AgentChangeSet::query()
                    ->whereBelongsTo($context->family)
                    ->whereBelongsTo($credential, 'credential')
                    ->whereKey($changeSetId)
                    ->where('status', 'previewed')
                    ->update(['status' => $terminalStatus, 'terminal_at' => now(), 'updated_at' => now()]);
            }

            throw $exception;
        }
    }

    private function assertDigest(AgentChangeSet $changeSet, string $digest): void
    {
        if ( ! hash_equals($changeSet->digest, $digest)) {
            throw new AgentApiException('digest_mismatch', 'The apply digest does not match the preview digest.', 409, '/digest');
        }
    }

    /** @param list<string> $acknowledgements */
    private function assertAcknowledgements(AgentChangeSet $changeSet, array $acknowledgements): void
    {
        $warnings = $changeSet->preview_document['warnings'] ?? null;
        if ( ! is_array($warnings) || ! array_is_list($warnings)) {
            throw new LogicException('A preview requires stable warning codes.');
        }
        sort($acknowledgements);
        $expected = array_values(array_filter($warnings, is_string(...)));
        sort($expected);
        if ($acknowledgements !== $expected) {
            throw new AgentApiException('warning_acknowledgement_mismatch', 'Apply must acknowledge exactly every preview warning code.', 409, '/warning_acknowledgements', details: ['expected' => $expected, 'provided' => $acknowledgements]);
        }
    }
}
