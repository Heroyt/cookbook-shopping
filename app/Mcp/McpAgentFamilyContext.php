<?php

declare(strict_types=1);

namespace App\Mcp;

use App\AgentIntegration\AgentCredentialAbility;
use App\AgentIntegration\Models\AgentCredential;
use App\FamilyAccess\AuthorizedFamilyContext;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Mcp\Actions\RevokeMcpOAuthTokens;
use App\Mcp\Models\McpAuthorization;
use App\Mcp\Models\McpOAuthUser;
use App\Mcp\Values\McpAgentAuthority;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Laravel\Passport\AccessToken;

final readonly class McpAgentFamilyContext
{
    public function __construct(private RevokeMcpOAuthTokens $revokeOAuthTokens) {}

    public function resolve(McpOAuthUser $oauthUser): McpAgentAuthority
    {
        $accessToken = $oauthUser->currentAccessToken();
        $clientId = $accessToken instanceof AccessToken ? $accessToken->oauth_client_id : null;

        if ( ! is_string($clientId) || ! $accessToken->can('mcp:use')) {
            throw new AuthenticationException();
        }

        $user = User::query()->find($oauthUser->getAuthIdentifier());
        if ( ! $user instanceof User) {
            throw new AuthenticationException();
        }

        $authorization = McpAuthorization::query()
            ->where('user_id', $user->id)
            ->where('passport_client_id', $clientId)
            ->with(['family', 'credential'])
            ->first();
        $family = $authorization?->family;
        $credential = $authorization?->credential;

        $hasInvalidAuthority = ! $authorization instanceof McpAuthorization
            || ! $family instanceof Family
            || ! $credential instanceof AgentCredential
            || $credential->tokenable_type !== User::class
            || $credential->tokenable_id !== $user->id
            || $credential->family_id !== $family->id
            || $credential->revoked_at !== null
            || $credential->expires_at === null
            || $credential->expires_at->isPast()
            || ! $credential->can(AgentCredentialAbility::ContentRead->value)
            || ! FamilyMembership::query()
                ->where('family_id', $family->id)
                ->where('user_id', $user->id)
                ->exists();

        if ($hasInvalidAuthority) {
            $this->revokeOAuthTokens->handle($user->id, $clientId);

            throw new AuthenticationException();
        }

        return new McpAgentAuthority(
            new AuthorizedFamilyContext($user, $family),
            $credential,
            $authorization,
        );
    }
}
