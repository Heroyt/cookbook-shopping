<?php

declare(strict_types=1);

namespace App\AgentIntegration\Actions;

use App\AgentIntegration\AgentCredentialRestrictionAction;
use App\AgentIntegration\Exceptions\AgentApiException;
use App\AgentIntegration\Models\AgentCredential;
use App\AgentIntegration\Values\AgentCredentialRestrictionResult;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;

final readonly class RestrictCurrentAgentCredential
{
    public function __construct(private RecordAgentCredentialRevocation $recordRevocation) {}

    public function handle(
        AgentCredential $requestCredential,
        AgentCredentialRestrictionAction $action,
        ?CarbonImmutable $requestedExpiry,
    ): AgentCredentialRestrictionResult {
        return DB::transaction(function () use ($requestCredential, $action, $requestedExpiry): AgentCredentialRestrictionResult {
            $credential = AgentCredential::query()
                ->lockForUpdate()
                ->find($requestCredential->id);

            if ( ! $credential instanceof AgentCredential || ! $this->hasLiveRequestAuthority($credential, $requestCredential)) {
                throw new AuthenticationException();
            }

            $changed = match ($action) {
                AgentCredentialRestrictionAction::ShortenExpiry => $this->shortenExpiry($credential, $requestedExpiry),
                AgentCredentialRestrictionAction::Revoke => $this->revoke($credential),
            };

            return new AgentCredentialRestrictionResult($credential->refresh(), $action, $changed);
        }, attempts: 3);
    }

    private function hasLiveRequestAuthority(
        AgentCredential $credential,
        AgentCredential $requestCredential,
    ): bool {
        if (
            $credential->revoked_at !== null
            || $credential->tokenable_type !== User::class
            || ! hash_equals($credential->token, $requestCredential->token)
            || ($credential->expires_at !== null && ! $credential->expires_at->isFuture())
        ) {
            return false;
        }

        return FamilyMembership::query()
            ->where('family_id', $credential->family_id)
            ->where('user_id', $credential->tokenable_id)
            ->exists();
    }

    private function shortenExpiry(AgentCredential $credential, ?CarbonImmutable $requestedExpiry): bool
    {
        if ($requestedExpiry === null || ! $requestedExpiry->isFuture()) {
            throw new AgentApiException(
                'validation_failed',
                'The requested expiry must still be in the future when the restriction is applied.',
                422,
                '/expires_at',
            );
        }

        if ($credential->expires_at !== null && ! $requestedExpiry->isBefore($credential->expires_at)) {
            return false;
        }

        $credential->forceFill(['expires_at' => $requestedExpiry])->save();

        return true;
    }

    private function revoke(AgentCredential $credential): bool
    {
        return $this->recordRevocation->handle($credential, null, 'self_revoked');
    }
}
