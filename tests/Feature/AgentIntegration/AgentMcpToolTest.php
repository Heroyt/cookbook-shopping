<?php

declare(strict_types=1);

namespace Tests\Feature\AgentIntegration;

use App\AgentIntegration\AgentCredentialAbility;
use App\AgentIntegration\Models\AgentChangeSet;
use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\Store;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Mcp\Actions\AuthorizeMcpConnection;
use App\Mcp\Models\McpOAuthUser;
use App\Mcp\Resources\AgentApiOpenApi;
use App\Mcp\Servers\AgentServer;
use App\Mcp\Tools\ApplyChangeSet;
use App\Mcp\Tools\GetChangeSet;
use App\Mcp\Tools\GetChangeSetExamples;
use App\Mcp\Tools\GetFamilyResource;
use App\Mcp\Tools\ListChangeSets;
use App\Mcp\Tools\ListFamilyCatalog;
use App\Mcp\Tools\PreviewChangeSet;
use App\Mcp\Tools\RestrictConnection;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Passport\AccessToken;
use Laravel\Passport\Client;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;
use Tests\Support\ConfiguresPassport;
use Tests\TestCase;

final class AgentMcpToolTest extends TestCase
{
    use ConfiguresPassport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configurePassport();
    }

    public function test_catalog_tools_read_only_from_the_credential_fixed_family(): void
    {
        [$oauthUser, $family] = $this->authority();
        $ingredient = Ingredient::factory()->for($family)->create(['name' => 'Máslo']);
        Ingredient::factory()->create(['name' => 'Cizí surovina']);

        AgentServer::actingAs($oauthUser, 'api')
            ->tool(ListFamilyCatalog::class, ['resource_type' => 'ingredients', 'status' => 'active'])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->has('data', 1)
                ->where('data.0.id', $ingredient->id)
                ->where('data.0.name', 'Máslo')
                ->where('data.0.resource_type', 'ingredients')
                ->etc());

        AgentServer::actingAs($oauthUser, 'api')
            ->tool(GetFamilyResource::class, ['resource_type' => 'ingredients', 'id' => $ingredient->id])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('data.id', $ingredient->id)
                ->where('data.name', 'Máslo')
                ->etc());
    }

    public function test_change_set_tools_preserve_preview_then_confirm_apply_protocol(): void
    {
        [$oauthUser] = $this->authority([AgentCredentialAbility::CookbookWrite]);
        $document = [
            'version' => 1,
            'client_request_id' => 'mcp-create-store',
            'operations' => [[
                'operation_id' => 'create-store',
                'resource_type' => 'stores',
                'action' => 'create',
                'local_ref' => 'new-store',
                'data' => ['name' => 'MCP obchod'],
            ]],
        ];

        $preview = AgentServer::actingAs($oauthUser, 'api')
            ->tool(PreviewChangeSet::class, ['document' => $document])
            ->assertOk();
        $preview->assertStructuredContent(fn (AssertableJson $json) => $json
            ->where('data.status', 'previewed')
            ->where('data.preview.warnings', [])
            ->etc());
        $this->assertDatabaseCount('stores', 0);

        $changeSet = AgentChangeSet::query()->sole();

        AgentServer::actingAs($oauthUser, 'api')
            ->tool(ApplyChangeSet::class, [
                'change_set_id' => $changeSet->id,
                'digest' => $changeSet->digest,
                'warning_acknowledgements' => [],
                'user_confirmation' => true,
            ])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('data.status', 'applied')
                ->where('data.identifier_mappings.new-store', fn (mixed $id): bool => is_int($id))
                ->etc());

        $this->assertSame('MCP obchod', Store::query()->sole()->name);
    }

    public function test_mcp_exposes_authoritative_change_set_examples_and_runtime_openapi(): void
    {
        [$oauthUser] = $this->authority();

        AgentServer::actingAs($oauthUser, 'api')
            ->tool(GetChangeSetExamples::class, ['resource_type' => 'recipes', 'action' => 'create'])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->has('data', 1)
                ->where('data.0.operations.0.resource_type', 'recipes')
                ->where('data.0.operations.0.action', 'create')
                ->where('data.0.operations.0.data.base_servings', '4')
                ->where('openapi_resource_uri', 'cookbook://agent-api/v1/openapi.json')
                ->etc());

        AgentServer::actingAs($oauthUser, 'api')
            ->resource(AgentApiOpenApi::class)
            ->assertOk()
            ->assertSee(['"openapi":"3.1.0"', 'CreateRecipeOperation', '"/change-sets"']);
    }

    public function test_history_tools_read_family_visible_change_sets(): void
    {
        [$oauthUser] = $this->authority([AgentCredentialAbility::CookbookWrite]);
        AgentServer::actingAs($oauthUser, 'api')->tool(PreviewChangeSet::class, ['document' => [
            'version' => 1,
            'client_request_id' => 'history-preview',
            'title' => 'Nový obchod',
            'operations' => [[
                'operation_id' => 'create-store',
                'resource_type' => 'stores',
                'action' => 'create',
                'local_ref' => 'store',
                'data' => ['name' => 'Historie'],
            ]],
        ]])->assertOk();
        $changeSet = AgentChangeSet::query()->sole();

        AgentServer::actingAs($oauthUser, 'api')
            ->tool(ListChangeSets::class, ['status' => 'previewed'])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->has('data', 1)
                ->where('data.0.id', $changeSet->id)
                ->where('data.0.title', 'Nový obchod')
                ->etc());

        AgentServer::actingAs($oauthUser, 'api')
            ->tool(GetChangeSet::class, ['change_set_id' => $changeSet->id])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('data.id', $changeSet->id)
                ->where('data.canonical_request.client_request_id', 'history-preview')
                ->etc());
    }

    public function test_connection_can_self_restrict_and_membership_loss_blocks_every_tool(): void
    {
        [$oauthUser, $family] = $this->authority();

        AgentServer::actingAs($oauthUser, 'api')
            ->tool(RestrictConnection::class, [
                'action' => 'shorten_expiry',
                'expires_at' => now()->addHour()->utc()->format('Y-m-d\TH:i:s\Z'),
            ])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('data.action', 'shorten_expiry')
                ->where('data.changed', true)
                ->etc());

        FamilyMembership::query()->where('family_id', $family->id)->delete();

        AgentServer::actingAs($oauthUser, 'api')
            ->tool(ListFamilyCatalog::class)
            ->assertHasErrors(['authentication_required']);
    }

    public function test_membership_loss_revokes_persisted_access_and_refresh_tokens(): void
    {
        [$oauthUser, $family] = $this->authority();
        $accessToken = $oauthUser->currentAccessToken();
        $this->assertInstanceOf(AccessToken::class, $accessToken);
        $clientId = $accessToken->oauth_client_id;
        $this->assertIsString($clientId);

        $persistedToken = Token::query()->create([
            'id' => str_repeat('a', 80),
            'user_id' => $oauthUser->id,
            'client_id' => $clientId,
            'scopes' => ['mcp:use'],
            'revoked' => false,
            'expires_at' => now()->addHour(),
        ]);
        RefreshToken::query()->create([
            'id' => str_repeat('b', 80),
            'access_token_id' => $persistedToken->id,
            'revoked' => false,
            'expires_at' => now()->addDay(),
        ]);

        FamilyMembership::query()->where('family_id', $family->id)->delete();

        AgentServer::actingAs($oauthUser, 'api')
            ->tool(ListFamilyCatalog::class)
            ->assertHasErrors(['authentication_required']);

        $this->assertDatabaseHas('oauth_access_tokens', [
            'id' => $persistedToken->id,
            'revoked' => true,
        ]);
        $this->assertDatabaseHas('oauth_refresh_tokens', [
            'access_token_id' => $persistedToken->id,
            'revoked' => true,
        ]);
    }

    public function test_mcp_tool_rates_are_scoped_to_the_linked_agent_credential(): void
    {
        [$oauthUser] = $this->authority();
        config(['agent-integration.rates.catalog_per_minute' => 1]);

        AgentServer::actingAs($oauthUser, 'api')
            ->tool(ListFamilyCatalog::class)
            ->assertOk();
        AgentServer::actingAs($oauthUser, 'api')
            ->tool(ListFamilyCatalog::class)
            ->assertHasErrors(['rate_limit_exceeded']);

        [$otherOAuthUser] = $this->authority();
        AgentServer::actingAs($otherOAuthUser, 'api')
            ->tool(ListFamilyCatalog::class)
            ->assertOk();
    }

    public function test_validation_errors_expose_only_stable_english_machine_details(): void
    {
        [$oauthUser] = $this->authority();

        AgentServer::actingAs($oauthUser, 'api')
            ->tool(GetFamilyResource::class, [
                'resource_type' => 'ingredients',
                'id' => 'not-an-integer',
            ])
            ->assertHasErrors(['validation_failed'])
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('error.code', 'validation_failed')
                ->where('error.message', 'The MCP tool arguments are invalid.')
                ->where('error.details.fields', ['id'])
                ->etc());
    }

    /**
     * @param  list<AgentCredentialAbility>  $abilities
     * @return array{McpOAuthUser, Family}
     */
    private function authority(array $abilities = []): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create();
        FamilyMembership::factory()->create(['user_id' => $user->id, 'family_id' => $family->id]);
        $user->forceFill(['current_family_id' => $family->id])->save();
        $client = Client::factory()->asPublic()->create();
        app(AuthorizeMcpConnection::class)->handle($user, $family, $client->id, $abilities);

        $oauthUser = McpOAuthUser::query()->findOrFail($user->id);
        $oauthUser->withAccessToken(new AccessToken([
            'oauth_client_id' => $client->id,
            'oauth_user_id' => (string) $user->id,
            'oauth_scopes' => ['mcp:use'],
        ]));

        return [$oauthUser, $family];
    }
}
