<?php

declare(strict_types=1);

namespace App\AgentIntegration\Http\Controllers;

use App\AgentIntegration\ChangeSets\AgentChangeSetHistory;
use App\AgentIntegration\Http\Requests\AgentChangeSetHistoryRequest;
use App\AgentIntegration\Models\AgentChangeSet;
use App\FamilyAccess\AuthorizedFamilyContext;
use App\FamilyAccess\CurrentFamilyScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

final class AgentChangeSetHistoryController extends Controller
{
    public function __construct(
        private readonly CurrentFamilyScope $currentFamilyScope,
        private readonly AgentChangeSetHistory $history,
    ) {}

    public function index(AgentChangeSetHistoryRequest $request): Response
    {
        $data = $this->currentFamilyScope->withinContext(
            $request->authenticatedUser(),
            function (AuthorizedFamilyContext $context) use ($request): array {
                $all = $this->history->list($context, [], appliedOnly: true);
                $filtered = $this->history->list($context, $request->filters(), appliedOnly: true);

                return [
                    'changeSets' => $filtered->map(fn (AgentChangeSet $changeSet): array => $this->webSummary($changeSet))->values(),
                    'credentials' => $this->credentialOptions($all),
                    'issuers' => $this->issuerOptions($all),
                ];
            },
        );

        return Inertia::render('agent-change-sets/Index', [
            ...$data,
            'filters' => $this->webFilters($request),
        ]);
    }

    public function show(AgentChangeSetHistoryRequest $request, string $agentChangeSet): Response
    {
        $changeSet = $this->currentFamilyScope->withinContext(
            $request->authenticatedUser(),
            fn (AuthorizedFamilyContext $context): AgentChangeSet => $this->history->detail($context, $agentChangeSet, appliedOnly: true),
        );

        return Inertia::render('agent-change-sets/Show', ['changeSet' => $this->webDetail($changeSet)]);
    }

    public function destroy(AgentChangeSetHistoryRequest $request, string $agentChangeSet): RedirectResponse
    {
        $this->currentFamilyScope->withinContext(
            $request->authenticatedUser(),
            fn (AuthorizedFamilyContext $context): null => $this->delete($context, $agentChangeSet),
        );
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Agent Change history deleted. Applied changes remain unchanged.'),
        ]);

        return to_route('agent-change-sets.index');
    }

    private function delete(AuthorizedFamilyContext $context, string $changeSetId): null
    {
        $this->history->deleteApplied($context, $changeSetId);

        return null;
    }

    /** @return array<string, mixed> */
    private function webSummary(AgentChangeSet $changeSet): array
    {
        return [
            'id' => $changeSet->id,
            'title' => $changeSet->title,
            'credentialName' => $changeSet->credential_name,
            'issuerName' => $changeSet->issuer_name,
            'resourceTypes' => $changeSet->resource_types,
            'outcome' => $changeSet->outcome,
            'operationCount' => $changeSet->operation_count,
            'appliedAt' => $changeSet->applied_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function webDetail(AgentChangeSet $changeSet): array
    {
        return [
            ...$this->webSummary($changeSet),
            'digest' => $changeSet->digest,
            'clientRequestId' => $changeSet->client_request_id,
            'sourceUrls' => $changeSet->source_urls,
            'note' => $changeSet->note,
            'canonicalRequest' => $changeSet->canonical_request,
            'preview' => $changeSet->preview_document,
            'warningAcknowledgements' => $changeSet->warning_acknowledgements ?? [],
            'identifierMappings' => $changeSet->identifier_mappings ?? [],
            'result' => $changeSet->result_document,
        ];
    }

    /**
     * @param  Collection<int, AgentChangeSet>  $changeSets
     * @return list<array{id: int, name: string}>
     */
    private function credentialOptions(Collection $changeSets): array
    {
        $options = [];
        $seen = [];
        foreach ($changeSets as $changeSet) {
            if (isset($seen[$changeSet->agent_credential_id])) {
                continue;
            }

            $seen[$changeSet->agent_credential_id] = true;
            $options[] = [
                'id' => $changeSet->agent_credential_id,
                'name' => $changeSet->credential_name,
            ];
        }

        return $options;
    }

    /**
     * @param  Collection<int, AgentChangeSet>  $changeSets
     * @return list<array{id: int, name: string}>
     */
    private function issuerOptions(Collection $changeSets): array
    {
        $options = [];
        $seen = [];
        foreach ($changeSets as $changeSet) {
            $issuerUserId = $changeSet->issuer_user_id;
            if ( ! is_int($issuerUserId) || isset($seen[$issuerUserId])) {
                continue;
            }

            $seen[$issuerUserId] = true;
            $options[] = [
                'id' => $issuerUserId,
                'name' => $changeSet->issuer_name,
            ];
        }

        return $options;
    }

    /** @return array<string, string|null> */
    private function webFilters(AgentChangeSetHistoryRequest $request): array
    {
        $filters = $request->filters();

        return [
            'credentialId' => $filters['credential_id'] !== null ? (string) $filters['credential_id'] : null,
            'issuerUserId' => $filters['issuer_user_id'] !== null ? (string) $filters['issuer_user_id'] : null,
            'dateFrom' => $filters['date_from'],
            'dateTo' => $filters['date_to'],
            'resourceType' => $filters['resource_type'],
            'outcome' => $filters['outcome'],
        ];
    }
}
