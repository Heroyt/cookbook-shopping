<?php

declare(strict_types=1);

namespace App\AgentIntegration\ChangeSets;

use App\AgentIntegration\Models\AgentChangeSet;
use App\FamilyAccess\AuthorizedFamilyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class AgentChangeSetHistory
{
    /**
     * @param  array{status?: string|null, credential_id?: int|null, issuer_user_id?: int|null, date_from?: string|null, date_to?: string|null, resource_type?: string|null, outcome?: string|null}  $filters
     * @return Collection<int, AgentChangeSet>
     */
    public function list(AuthorizedFamilyContext $context, array $filters, bool $appliedOnly = false): Collection
    {
        return $this->filteredQuery($context, $filters, $appliedOnly)
            ->latest('created_at')
            ->latest('id')
            ->get();
    }

    public function detail(AuthorizedFamilyContext $context, string $changeSetId, bool $appliedOnly = false): AgentChangeSet
    {
        return AgentChangeSet::query()
            ->whereBelongsTo($context->family)
            ->when($appliedOnly, fn (Builder $query): Builder => $query->where('status', 'applied'))
            ->findOrFail($changeSetId);
    }

    public function deleteApplied(AuthorizedFamilyContext $context, string $changeSetId): void
    {
        AgentChangeSet::query()
            ->whereBelongsTo($context->family)
            ->where('status', 'applied')
            ->whereKey($changeSetId)
            ->lockForUpdate()
            ->firstOrFail()
            ->delete();
    }

    /**
     * @param  array{status?: string|null, credential_id?: int|null, issuer_user_id?: int|null, date_from?: string|null, date_to?: string|null, resource_type?: string|null, outcome?: string|null}  $filters
     * @return Builder<AgentChangeSet>
     */
    private function filteredQuery(AuthorizedFamilyContext $context, array $filters, bool $appliedOnly): Builder
    {
        $dateColumn = $appliedOnly ? 'applied_at' : 'created_at';
        $status = $filters['status'] ?? null;
        $credentialId = $filters['credential_id'] ?? null;
        $issuerUserId = $filters['issuer_user_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $resourceType = $filters['resource_type'] ?? null;
        $outcome = $filters['outcome'] ?? null;

        return AgentChangeSet::query()
            ->whereBelongsTo($context->family)
            ->when($appliedOnly, fn (Builder $query): Builder => $query->where('status', 'applied'))
            ->when( ! $appliedOnly && $status !== null, fn (Builder $query): Builder => $query->where('status', $status))
            ->when($credentialId !== null, fn (Builder $query): Builder => $query->where('agent_credential_id', $credentialId))
            ->when($issuerUserId !== null, fn (Builder $query): Builder => $query->where('issuer_user_id', $issuerUserId))
            ->when($dateFrom !== null, fn (Builder $query): Builder => $query->whereDate($dateColumn, '>=', $dateFrom))
            ->when($dateTo !== null, fn (Builder $query): Builder => $query->whereDate($dateColumn, '<=', $dateTo))
            ->when($resourceType !== null, fn (Builder $query): Builder => $query->whereJsonContains('resource_types', $resourceType))
            ->when($outcome !== null, fn (Builder $query): Builder => $query->where('outcome', $outcome));
    }
}
