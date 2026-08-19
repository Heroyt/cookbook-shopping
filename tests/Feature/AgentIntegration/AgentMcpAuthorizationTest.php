<?php

declare(strict_types=1);

namespace Tests\Feature\AgentIntegration;

use App\AgentIntegration\AgentCredentialAbility;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Mcp\Actions\AuthorizeMcpConnection;
use App\Mcp\McpAgentFamilyContext;
use App\Mcp\Models\McpAuthorization;
use App\Mcp\Models\McpOAuthUser;
use App\Models\User;
use Laravel\Passport\AccessToken;
use Laravel\Passport\Client;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;
use Tests\Support\ConfiguresPassport;
use Tests\TestCase;

final class AgentMcpAuthorizationTest extends TestCase
{
    use ConfiguresPassport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configurePassport();
        $this->withoutVite();
    }

    public function test_authorization_captures_one_family_and_issues_an_ordinary_agent_credential(): void
    {
        $user = User::factory()->create(['name' => 'Alena Agentová']);
        $authorizedFamily = Family::factory()->create(['name' => 'Domov']);
        $otherFamily = Family::factory()->create(['name' => 'Chata']);
        FamilyMembership::factory()->create(['user_id' => $user->id, 'family_id' => $authorizedFamily->id]);
        FamilyMembership::factory()->create(['user_id' => $user->id, 'family_id' => $otherFamily->id]);
        $user->forceFill(['current_family_id' => $authorizedFamily->id])->save();
        $client = Client::factory()->asPublic()->create(['name' => 'ChatGPT']);

        $authorization = app(AuthorizeMcpConnection::class)->handle(
            $user,
            $client->id,
            [AgentCredentialAbility::CookbookWrite, AgentCredentialAbility::PlanningWrite],
        );

        $credential = $authorization->credential()->sole();
        $this->assertSame($user->id, $authorization->user_id);
        $this->assertSame($authorizedFamily->id, $authorization->family_id);
        $this->assertSame($client->id, $authorization->passport_client_id);
        $this->assertSame($authorizedFamily->id, $credential->family_id);
        $this->assertSame('Připojení MCP', $credential->name);
        $this->assertSame([
            AgentCredentialAbility::ContentRead->value,
            AgentCredentialAbility::CookbookWrite->value,
            AgentCredentialAbility::PlanningWrite->value,
        ], $credential->abilities);

        $user->forceFill(['current_family_id' => $otherFamily->id])->save();

        $this->assertSame($authorizedFamily->id, $authorization->fresh()->family_id);
        $this->assertSame($otherFamily->id, $user->fresh()->current_family_id);
    }

    public function test_passport_authority_resolves_the_linked_family_instead_of_current_family(): void
    {
        $user = User::factory()->create();
        $authorizedFamily = Family::factory()->create();
        $currentFamily = Family::factory()->create();
        FamilyMembership::factory()->create(['user_id' => $user->id, 'family_id' => $authorizedFamily->id]);
        FamilyMembership::factory()->create(['user_id' => $user->id, 'family_id' => $currentFamily->id]);
        $user->forceFill(['current_family_id' => $authorizedFamily->id])->save();
        $client = Client::factory()->asPublic()->create();
        $authorization = app(AuthorizeMcpConnection::class)->handle($user, $client->id, []);

        $user->forceFill(['current_family_id' => $currentFamily->id])->save();
        $oauthUser = McpOAuthUser::query()->findOrFail($user->id);
        $oauthUser->withAccessToken(new AccessToken([
            'oauth_client_id' => $client->id,
            'oauth_user_id' => (string) $user->id,
            'oauth_scopes' => ['mcp:use'],
        ]));

        $authority = app(McpAgentFamilyContext::class)->resolve($oauthUser);

        $this->assertSame($authorization->id, $authority->authorization->id);
        $this->assertSame($authorizedFamily->id, $authority->context->family->id);
        $this->assertSame($authorization->agent_credential_id, $authority->credential->id);
        $this->assertSame($currentFamily->id, $user->fresh()->current_family_id);
    }

    public function test_oauth_consent_is_czech_and_names_the_current_family_and_selected_authority(): void
    {
        $user = User::factory()->create(['email' => 'alena@example.test']);
        $family = Family::factory()->create(['name' => 'Domov']);
        FamilyMembership::factory()->create(['user_id' => $user->id, 'family_id' => $family->id]);
        $user->forceFill(['current_family_id' => $family->id])->save();
        $client = Client::factory()->asPublic()->create([
            'name' => 'ChatGPT',
            'redirect_uris' => ['https://chatgpt.com/connector_platform_oauth_redirect'],
        ]);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => now()->unix()])
            ->get('/oauth/authorize?' . http_build_query([
                'client_id' => $client->id,
                'redirect_uri' => 'https://chatgpt.com/connector_platform_oauth_redirect',
                'response_type' => 'code',
                'scope' => 'mcp:use',
                'state' => 'state-123',
                'code_challenge' => str_repeat('a', 43),
                'code_challenge_method' => 'S256',
            ]))
            ->assertOk()
            ->assertSeeText('Povolit přístup agentovi')
            ->assertSeeText('Domov')
            ->assertSeeText('Čtení obsahu')
            ->assertSeeText('Úpravy kuchařky')
            ->assertSeeText('Úpravy kalendáře')
            ->assertSeeText('Archivace a mazání')
            ->assertSee('name="abilities[]"', escape: false);
    }

    public function test_oauth_consent_requires_recent_password_confirmation(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create();
        FamilyMembership::factory()->create(['user_id' => $user->id, 'family_id' => $family->id]);
        $user->forceFill(['current_family_id' => $family->id])->save();
        $client = Client::factory()->asPublic()->create([
            'redirect_uris' => ['https://chatgpt.com/connector_platform_oauth_redirect'],
        ]);

        $this->actingAs($user)->get('/oauth/authorize?' . http_build_query([
            'client_id' => $client->id,
            'redirect_uri' => 'https://chatgpt.com/connector_platform_oauth_redirect',
            'response_type' => 'code',
            'scope' => 'mcp:use',
            'state' => 'state-123',
            'code_challenge' => str_repeat('a', 43),
            'code_challenge_method' => 'S256',
        ]))->assertRedirect(route('password.confirm'));
    }

    public function test_oauth_approval_issues_the_selected_fixed_family_authority(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['name' => 'Domov']);
        FamilyMembership::factory()->create(['user_id' => $user->id, 'family_id' => $family->id]);
        $user->forceFill(['current_family_id' => $family->id])->save();
        $client = Client::factory()->asPublic()->create([
            'name' => 'ChatGPT',
            'redirect_uris' => ['https://chatgpt.com/connector_platform_oauth_redirect'],
        ]);
        $session = ['auth.password_confirmed_at' => now()->unix()];
        $query = http_build_query([
            'client_id' => $client->id,
            'redirect_uri' => 'https://chatgpt.com/connector_platform_oauth_redirect',
            'response_type' => 'code',
            'scope' => 'mcp:use',
            'state' => 'state-123',
            'code_challenge' => str_repeat('a', 43),
            'code_challenge_method' => 'S256',
        ]);

        $consent = $this->actingAs($user)->withSession($session)->get('/oauth/authorize?' . $query)->assertOk();
        preg_match('/name="auth_token" value="([^"]+)"/', $consent->getContent(), $matches);
        $this->assertArrayHasKey(1, $matches);

        $this->actingAs($user)->withSession($session)->post('/oauth/authorize', [
            'auth_token' => $matches[1],
            'abilities' => [
                AgentCredentialAbility::CookbookWrite->value,
                AgentCredentialAbility::PlanningWrite->value,
            ],
        ])->assertRedirectContains('https://chatgpt.com/connector_platform_oauth_redirect?code=');

        $authorization = McpAuthorization::query()->sole();
        $this->assertSame($family->id, $authorization->family_id);
        $this->assertSame($client->id, $authorization->passport_client_id);
        $this->assertSame([
            AgentCredentialAbility::ContentRead->value,
            AgentCredentialAbility::CookbookWrite->value,
            AgentCredentialAbility::PlanningWrite->value,
        ], $authorization->credential()->sole()->abilities);
    }

    public function test_explicit_reauthorization_replaces_the_old_credential_and_recaptures_current_family(): void
    {
        $user = User::factory()->create();
        $firstFamily = Family::factory()->create();
        $secondFamily = Family::factory()->create();
        FamilyMembership::factory()->create(['user_id' => $user->id, 'family_id' => $firstFamily->id]);
        FamilyMembership::factory()->create(['user_id' => $user->id, 'family_id' => $secondFamily->id]);
        $user->forceFill(['current_family_id' => $firstFamily->id])->save();
        $client = Client::factory()->asPublic()->create();
        $first = app(AuthorizeMcpConnection::class)->handle($user, $client->id, []);
        $oldCredential = $first->credential()->sole();
        $oldAccessToken = Token::query()->create([
            'id' => str_repeat('a', 80),
            'user_id' => $user->id,
            'client_id' => $client->id,
            'scopes' => ['mcp:use'],
            'revoked' => false,
            'expires_at' => now()->addHour(),
        ]);
        RefreshToken::query()->create([
            'id' => str_repeat('b', 80),
            'access_token_id' => $oldAccessToken->id,
            'revoked' => false,
            'expires_at' => now()->addDay(),
        ]);

        $user->forceFill(['current_family_id' => $secondFamily->id])->save();
        $reauthorized = app(AuthorizeMcpConnection::class)->handle(
            $user,
            $client->id,
            [AgentCredentialAbility::CookbookWrite],
        );

        $this->assertSame($first->id, $reauthorized->id);
        $this->assertSame($secondFamily->id, $reauthorized->family_id);
        $this->assertNotSame($oldCredential->id, $reauthorized->agent_credential_id);
        $this->assertSame('mcp_reauthorized', $oldCredential->fresh()?->revocation_reason);
        $this->assertSame([
            AgentCredentialAbility::ContentRead->value,
            AgentCredentialAbility::CookbookWrite->value,
        ], $reauthorized->credential()->sole()->abilities);
        $this->assertDatabaseHas('oauth_access_tokens', [
            'id' => $oldAccessToken->id,
            'revoked' => true,
        ]);
        $this->assertDatabaseHas('oauth_refresh_tokens', [
            'access_token_id' => $oldAccessToken->id,
            'revoked' => true,
        ]);
    }

    public function test_oauth_code_exchange_can_initialize_the_laravel_mcp_endpoint(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create();
        FamilyMembership::factory()->create(['user_id' => $user->id, 'family_id' => $family->id]);
        $user->forceFill(['current_family_id' => $family->id])->save();
        $redirectUri = 'https://chatgpt.com/connector_platform_oauth_redirect';
        $client = Client::factory()->asPublic()->create(['redirect_uris' => [$redirectUri]]);
        $verifier = str_repeat('v', 64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $session = ['auth.password_confirmed_at' => now()->unix()];

        $consent = $this->actingAs($user)->withSession($session)->get('/oauth/authorize?' . http_build_query([
            'client_id' => $client->id,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'mcp:use',
            'state' => 'state-123',
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ]))->assertOk();
        preg_match('/name="auth_token" value="([^"]+)"/', $consent->getContent(), $matches);
        $this->assertArrayHasKey(1, $matches);

        $approval = $this->actingAs($user)->withSession($session)->post('/oauth/authorize', [
            'auth_token' => $matches[1],
            'abilities' => [AgentCredentialAbility::CookbookWrite->value],
        ])->assertRedirect();
        parse_str((string) parse_url((string) $approval->headers->get('Location'), PHP_URL_QUERY), $callbackQuery);
        $this->assertIsString($callbackQuery['code'] ?? null);

        $token = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->id,
            'redirect_uri' => $redirectUri,
            'code' => $callbackQuery['code'],
            'code_verifier' => $verifier,
        ])->assertOk()->json('access_token');
        $this->assertIsString($token);

        $this->withToken($token)->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-06-18',
                'capabilities' => [],
                'clientInfo' => ['name' => 'PHPUnit', 'version' => '1.0.0'],
            ],
        ])->assertOk()
            ->assertJsonPath('result.serverInfo.name', 'Cookbook Agent')
            ->assertJsonPath('result.protocolVersion', '2025-06-18');
    }
}
