<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\AgentIntegration\Actions\RestrictCurrentAgentCredential;
use App\AgentIntegration\AgentCredentialRestrictionAction;
use App\AgentIntegration\AgentCredentialRestrictionPresenter;
use App\Mcp\Actions\RevokeMcpOAuthTokens;
use App\Mcp\McpAgentFamilyContext;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[IsDestructive]
#[IsIdempotent]
#[IsOpenWorld(false)]
final class RestrictConnection extends McpAgentTool
{
    protected string $name = 'restrict_connection';

    protected string $title = 'Restrict Current MCP Connection';

    protected string $description = 'Irreversibly shorten this connection expiry or revoke it immediately. Never extends authority. After success, clearly report the new expiry or revocation to the user.';

    public function __construct(
        McpAgentFamilyContext $familyContext,
        private readonly RestrictCurrentAgentCredential $restrict,
        private readonly AgentCredentialRestrictionPresenter $presenter,
        private readonly RevokeMcpOAuthTokens $revokeOAuthTokens,
    ) {
        parent::__construct($familyContext);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->enum(AgentCredentialRestrictionAction::class)->required(),
            'expires_at' => $schema->string()->description('Required only for shorten_expiry. RFC 3339 UTC whole-second timestamp still in the future.'),
        ];
    }

    public function handle(Request $request): ResponseFactory
    {
        return $this->respond(function () use ($request): array {
            $validated = $request->validate([
                'action' => ['required', Rule::enum(AgentCredentialRestrictionAction::class)],
                'expires_at' => [
                    Rule::requiredIf($request->get('action') === AgentCredentialRestrictionAction::ShortenExpiry->value),
                    Rule::prohibitedIf($request->get('action') === AgentCredentialRestrictionAction::Revoke->value),
                    'string',
                    'date_format:Y-m-d\TH:i:s\Z',
                    'after:now',
                ],
            ]);
            $authority = $this->authority($request);
            $this->enforceRateLimit($authority, 'credential-restriction', 'credential_restriction_per_minute');
            $action = AgentCredentialRestrictionAction::from($this->stringArgument($request, 'action'));
            $expiresAtValue = $this->nullableStringArgument($request, 'expires_at');
            $expiresAt = $expiresAtValue !== null
                ? CarbonImmutable::parse($expiresAtValue, 'UTC')
                : null;
            $result = $this->restrict->handle($authority->credential, $action, $expiresAt);

            if ($action === AgentCredentialRestrictionAction::Revoke) {
                $this->revokeOAuthTokens->handle(
                    $authority->context->user->id,
                    $authority->authorization->passport_client_id,
                );
            }

            return ['data' => $this->presenter->present($result)];
        });
    }
}
