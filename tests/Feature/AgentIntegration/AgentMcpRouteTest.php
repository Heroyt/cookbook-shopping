<?php

declare(strict_types=1);

namespace Tests\Feature\AgentIntegration;

use App\Mcp\Models\McpOAuthUser;
use App\Models\User;
use Laravel\Passport\Passport;
use Tests\Support\ConfiguresPassport;
use Tests\TestCase;

final class AgentMcpRouteTest extends TestCase
{
    use ConfiguresPassport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configurePassport();
    }

    public function test_mcp_publishes_oauth_discovery_for_the_fixed_endpoint(): void
    {
        $this->getJson('/.well-known/oauth-protected-resource/mcp')
            ->assertOk()
            ->assertExactJson([
                'resource' => 'http://localhost/mcp',
                'authorization_servers' => ['http://localhost'],
                'scopes_supported' => ['mcp:use'],
            ]);

        $this->getJson('/.well-known/oauth-authorization-server')
            ->assertOk()
            ->assertJsonPath('issuer', 'http://localhost')
            ->assertJsonPath('authorization_endpoint', 'http://localhost/oauth/authorize')
            ->assertJsonPath('token_endpoint', 'http://localhost/oauth/token')
            ->assertJsonPath('registration_endpoint', 'http://localhost/oauth/register')
            ->assertJsonPath('code_challenge_methods_supported.0', 'S256')
            ->assertJsonPath('scopes_supported.0', 'mcp:use');
    }

    public function test_mcp_endpoint_rejects_requests_without_oauth_authority(): void
    {
        $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [],
        ])->assertUnauthorized()
            ->assertHeader(
                'WWW-Authenticate',
                'Bearer realm="mcp", resource_metadata="http://localhost/.well-known/oauth-protected-resource/mcp"',
            );
    }

    public function test_mcp_endpoint_rejects_an_oauth_token_without_fixed_family_authority(): void
    {
        $user = User::factory()->create();
        $mcpUser = McpOAuthUser::query()->findOrFail($user->id);
        Passport::actingAs($mcpUser, ['mcp:use']);

        $this->postJson('/mcp', $this->initializeRequest())
            ->assertUnauthorized();
    }

    public function test_dynamic_registration_accepts_only_the_chatgpt_redirect_origin(): void
    {
        $this->postJson('/oauth/register', [
            'client_name' => 'ChatGPT',
            'redirect_uris' => ['https://chatgpt.com/connector_platform_oauth_redirect'],
        ])->assertCreated()
            ->assertJsonPath('scope', 'mcp:use')
            ->assertJsonPath('token_endpoint_auth_method', 'none');

        $this->postJson('/oauth/register', [
            'client_name' => 'Neznámý klient',
            'redirect_uris' => ['https://example.test/oauth/callback'],
        ])->assertBadRequest()
            ->assertJsonPath('error', 'invalid_redirect_uri');
    }

    /** @return array<string, mixed> */
    private function initializeRequest(): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-06-18',
                'capabilities' => [],
                'clientInfo' => ['name' => 'PHPUnit', 'version' => '1.0.0'],
            ],
        ];
    }
}
