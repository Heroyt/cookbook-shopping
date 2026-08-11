<?php

declare(strict_types=1);

namespace App\AgentIntegration\Actions;

use App\AgentIntegration\AgentCredentialAbility;
use App\AgentIntegration\Models\AgentCredential;
use App\AgentIntegration\Values\IssuedAgentCredential;
use App\FamilyAccess\AuthorizedFamilyContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use LogicException;

final readonly class RotateAgentCredential
{
    public function __construct(private IssueAgentCredential $issueAgentCredential) {}

    public function handle(AuthorizedFamilyContext $context, int $credentialId): IssuedAgentCredential
    {
        return DB::transaction(function () use ($context, $credentialId): IssuedAgentCredential {
            $credential = AgentCredential::query()
                ->whereBelongsTo($context->family)
                ->lockForUpdate()
                ->findOrFail($credentialId);

            if ($credential->tokenable_type !== $context->user::class
                || $credential->tokenable_id !== $context->user->id) {
                throw new AuthorizationException('Only the issuing User may rotate this Agent Credential.');
            }

            if ($credential->revoked_at !== null) {
                throw new LogicException('A revoked Agent Credential cannot be rotated.');
            }

            $additionalAbilities = array_values(collect($credential->abilities)
                ->map(fn (string $ability): ?AgentCredentialAbility => AgentCredentialAbility::tryFrom($ability))
                ->filter(fn (?AgentCredentialAbility $ability): bool => $ability !== null
                    && $ability !== AgentCredentialAbility::ContentRead)
                ->all());

            $replacement = $this->issueAgentCredential->handle(
                $context,
                $credential->name,
                $additionalAbilities,
            );

            $credential->forceFill([
                'revoked_at' => now(),
                'revoked_by_user_id' => $context->user->id,
                'revocation_reason' => 'rotated',
                'rotated_to_id' => $replacement->credential->id,
            ])->save();

            return $replacement;
        });
    }
}
