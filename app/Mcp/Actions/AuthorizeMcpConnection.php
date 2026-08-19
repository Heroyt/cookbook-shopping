<?php

declare(strict_types=1);

namespace App\Mcp\Actions;

use App\AgentIntegration\Actions\IssueAgentCredential;
use App\AgentIntegration\Actions\RecordAgentCredentialRevocation;
use App\AgentIntegration\AgentCredentialAbility;
use App\AgentIntegration\Models\AgentCredential;
use App\FamilyAccess\AuthorizedFamilyContext;
use App\FamilyAccess\Models\Family;
use App\Mcp\Models\McpAuthorization;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class AuthorizeMcpConnection
{
    public function __construct(
        private IssueAgentCredential $issueAgentCredential,
        private RecordAgentCredentialRevocation $recordRevocation,
        private RevokeMcpOAuthTokens $revokeOAuthTokens,
    ) {}

    /** @param list<AgentCredentialAbility> $abilities */
    public function handle(User $user, Family $family, string $passportClientId, array $abilities): McpAuthorization
    {
        return DB::transaction(function () use ($user, $family, $passportClientId, $abilities): McpAuthorization {
            $existing = McpAuthorization::query()
                ->whereBelongsTo($user)
                ->where('passport_client_id', $passportClientId)
                ->lockForUpdate()
                ->first();

            $issued = $this->issueAgentCredential->handle(
                new AuthorizedFamilyContext($user, $family),
                __('Připojení MCP'),
                $abilities,
            );

            if ($existing instanceof McpAuthorization) {
                $this->revokeOAuthTokens->handle($user->id, $passportClientId);
                $oldCredential = $existing->credential()->lockForUpdate()->first();
                if ($oldCredential instanceof AgentCredential) {
                    $this->recordRevocation->handle(
                        $oldCredential,
                        $user->id,
                        'mcp_reauthorized',
                        $issued->credential->id,
                    );
                }

                $existing->forceFill([
                    'family_id' => $family->id,
                    'agent_credential_id' => $issued->credential->id,
                ])->save();

                return $existing->refresh();
            }

            return McpAuthorization::query()->create([
                'user_id' => $user->id,
                'family_id' => $family->id,
                'passport_client_id' => $passportClientId,
                'agent_credential_id' => $issued->credential->id,
            ]);
        }, 3);
    }
}
