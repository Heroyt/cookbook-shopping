<?php

declare(strict_types=1);

namespace Tests\Feature\AgentIntegration;

use App\AgentIntegration\Actions\IssueAgentCredential;
use App\AgentIntegration\AgentCredentialAbility;
use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\RecipeTag;
use App\Cookbook\Models\Store;
use App\Cookbook\Models\StoreSection;
use App\FamilyAccess\AuthorizedFamilyContext;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\MealPlanning\Models\CalendarEntry;
use App\Models\User;
use Dedoc\Scramble\Scramble;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Tests\TestCase;

final class AgentOpenApiTest extends TestCase
{
    public function test_public_versioned_documentation_routes_expose_only_the_agent_api(): void
    {
        $scramble = config('scramble');
        $scramble['renderers']['elements']['hideTryIt'] = true;
        Scramble::configure()->useConfig($scramble);

        $this->get('/docs/agent-api/v1')
            ->assertOk()
            ->assertSee('Agent API v1')
            ->assertSee('hideTryIt="true"', escape: false);

        $document = $this->getJson('/docs/agent-api/v1/openapi.json')
            ->assertOk()
            ->assertJsonPath('openapi', '3.1.0')
            ->assertJsonPath('info.version', '1.0.0')
            ->assertJsonPath('components.securitySchemes.http.type', 'http')
            ->assertJsonPath('components.securitySchemes.http.scheme', 'bearer')
            ->json();

        $this->assertCount(1, $document['servers']);
        $this->assertStringEndsWith('/api/v1', $document['servers'][0]['url']);
        $paths = array_keys($document['paths']);
        sort($paths);
        $this->assertSame([
            '/catalog',
            '/catalog/{resourceType}/{id}',
            '/change-sets',
            '/change-sets/{changeSet}',
            '/change-sets/{changeSet}/apply',
        ], $paths);
        $this->assertArrayHasKey('AgentApiError', $document['components']['schemas']);
        $this->assertArrayHasKey('AgentChangeSetDocument', $document['components']['schemas']);

        $schemas = $document['components']['schemas'];
        $this->assertSame('object', $schemas['AgentApiError']['properties']['error']['properties']['details']['type']);
        $changeSetId = $schemas['AgentChangeSet']['properties']['id'];
        $this->assertSame('string', $changeSetId['type']);
        $this->assertSame(26, $changeSetId['minLength']);
        $this->assertSame(26, $changeSetId['maxLength']);
        $this->assertSame('^[0-7][0-9A-HJKMNP-TV-Z]{25}$', $changeSetId['pattern']);
        $this->assertArrayNotHasKey('format', $changeSetId);

        foreach ([
            $schemas['AgentChangeSetDocument']['properties']['supersedes_id'],
            $schemas['AgentChangeSet']['properties']['supersedes_id'],
        ] as $supersedesId) {
            $this->assertSame(26, $supersedesId['minLength']);
            $this->assertSame(26, $supersedesId['maxLength']);
            $this->assertSame('^[0-7][0-9A-HJKMNP-TV-Z]{25}$', $supersedesId['pattern']);
            $this->assertArrayNotHasKey('format', $supersedesId);
        }

        $this->assertCount(20, $schemas['AgentChangeSetOperation']['oneOf']);
        $this->assertSame([
            '#/components/schemas/CreateStoreOperation',
            '#/components/schemas/UpdateStoreOperation',
            '#/components/schemas/DeleteStoreOperation',
            '#/components/schemas/CreateStoreSectionOperation',
            '#/components/schemas/UpdateStoreSectionOperation',
            '#/components/schemas/DeleteStoreSectionOperation',
            '#/components/schemas/CreateIngredientOperation',
            '#/components/schemas/UpdateIngredientOperation',
            '#/components/schemas/ArchiveIngredientOperation',
            '#/components/schemas/RestoreIngredientOperation',
            '#/components/schemas/CreateRecipeTagOperation',
            '#/components/schemas/UpdateRecipeTagOperation',
            '#/components/schemas/DeleteRecipeTagOperation',
            '#/components/schemas/CreateRecipeOperation',
            '#/components/schemas/UpdateRecipeOperation',
            '#/components/schemas/ArchiveRecipeOperation',
            '#/components/schemas/RestoreRecipeOperation',
            '#/components/schemas/CreateCalendarEntryOperation',
            '#/components/schemas/UpdateCalendarEntryOperation',
            '#/components/schemas/DeleteCalendarEntryOperation',
        ], array_column($schemas['AgentChangeSetOperation']['oneOf'], '$ref'));

        $createStore = $schemas['CreateStoreOperation'];
        $this->assertSame(['operation_id', 'resource_type', 'action', 'local_ref', 'data'], $createStore['required']);
        $this->assertSame('stores', $createStore['properties']['resource_type']['const']);
        $this->assertSame('create', $createStore['properties']['action']['const']);
        $this->assertSame(['name'], $createStore['properties']['data']['required']);
        $this->assertFalse($createStore['additionalProperties']);

        $updateIngredient = $schemas['UpdateIngredientOperation'];
        $this->assertSame(
            ['operation_id', 'resource_type', 'action', 'resource_id', 'expected_updated_at'],
            $updateIngredient['required'],
        );
        $this->assertSame([
            ['required' => ['set']],
            ['required' => ['unset']],
        ], $updateIngredient['anyOf']);
        $this->assertSame(1, $updateIngredient['properties']['set']['minProperties']);
        $this->assertSame(1, $updateIngredient['properties']['unset']['minItems']);

        $packageQuantities = $schemas['CreateIngredientOperation']['properties']['data']['properties']['package_quantities'];
        $this->assertCount(3, $packageQuantities['oneOf']);
        $this->assertSame(['weight_grams'], $packageQuantities['oneOf'][0]['required']);
        $this->assertSame('string', $packageQuantities['oneOf'][0]['properties']['weight_grams']['type']);
        $this->assertSame('null', $packageQuantities['oneOf'][0]['properties']['volume_millilitres']['type']);
        $this->assertSame(['volume_millilitres'], $packageQuantities['oneOf'][1]['required']);
        $this->assertSame('null', $packageQuantities['oneOf'][1]['properties']['weight_grams']['type']);
        $this->assertSame('string', $packageQuantities['oneOf'][1]['properties']['volume_millilitres']['type']);
        $this->assertSame(['piece_count'], $packageQuantities['oneOf'][2]['required']);
        $this->assertSame('null', $packageQuantities['oneOf'][2]['properties']['weight_grams']['type']);
        $this->assertSame('null', $packageQuantities['oneOf'][2]['properties']['volume_millilitres']['type']);
        $this->assertSame('string', $packageQuantities['oneOf'][2]['properties']['piece_count']['type']);

        $examples = $schemas['AgentChangeSetDocument']['examples'];
        $this->assertCount(20, $examples);
        $pairs = array_map(
            fn (array $example): string => $example['operations'][0]['resource_type'] . '/' . $example['operations'][0]['action'],
            $examples,
        );
        sort($pairs);
        $this->assertSame([
            'calendar_entries/create',
            'calendar_entries/delete',
            'calendar_entries/update',
            'ingredients/archive',
            'ingredients/create',
            'ingredients/restore',
            'ingredients/update',
            'recipe_tags/create',
            'recipe_tags/delete',
            'recipe_tags/update',
            'recipes/archive',
            'recipes/create',
            'recipes/restore',
            'recipes/update',
            'store_sections/create',
            'store_sections/delete',
            'store_sections/update',
            'stores/create',
            'stores/delete',
            'stores/update',
        ], $pairs);

        $this->get('/docs/api')->assertNotFound();
        $this->get('/docs/api.json')->assertNotFound();
    }

    public function test_every_agent_data_route_is_protected_by_sanctum_and_an_ability(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn (Route $route): bool => str_starts_with($route->uri(), 'api/v1/'));

        $this->assertCount(6, $routes);
        foreach ($routes as $route) {
            $middleware = $route->gatherMiddleware();

            $this->assertContains('auth:sanctum', $middleware, $route->uri());
            $this->assertContains('abilities:content:read', $middleware, $route->uri());
        }
    }

    public function test_every_published_operation_example_is_accepted_by_the_preview_runtime(): void
    {
        [$family, $secret] = $this->credential();
        $examples = $this->getJson('/docs/agent-api/v1/openapi.json')
            ->assertOk()
            ->json('components.schemas.AgentChangeSetDocument.examples');
        $this->assertIsArray($examples);

        foreach ($examples as $example) {
            $this->assertIsArray($example);
            $operation = $example['operations'][0] ?? null;
            $this->assertIsArray($operation);
            $resourceType = $operation['resource_type'] ?? null;
            $action = $operation['action'] ?? null;
            $this->assertIsString($resourceType);
            $this->assertIsString($action);

            $example['operations'][0] = $this->prepareOperation($family, $operation, $resourceType, $action);
            $response = $this->withToken($secret)->postJson('/api/v1/change-sets', $example);
            $this->assertSame(201, $response->status(), $resourceType . '/' . $action . ': ' . $response->content());
        }
    }

    /**
     * @param  array<string, mixed>  $operation
     * @return array<string, mixed>
     */
    private function prepareOperation(Family $family, array $operation, string $resourceType, string $action): array
    {
        if ($action === 'create') {
            if ($resourceType === 'recipes') {
                $ingredient = Ingredient::factory()->for($family)->create();
                $tag = RecipeTag::factory()->for($family)->create();
                $operation['data']['ingredients'][0]['ingredient_id'] = $ingredient->id;
                $operation['data']['recipe_tag_ids'] = [$tag->id];
            } elseif ($resourceType === 'calendar_entries') {
                $operation['data']['recipe_id'] = Recipe::factory()->for($family)->create()->id;
            }

            return $operation;
        }

        $model = $this->existingResource($family, $resourceType);
        if ($action === 'restore') {
            $model->forceFill(['archived_at' => now()])->save();
        }
        $model->refresh();
        $operation['resource_id'] = $model->getKey();
        $operation['expected_updated_at'] = $model->updated_at?->utc()->format('Y-m-d\TH:i:s\Z');

        return $operation;
    }

    private function existingResource(Family $family, string $resourceType): Model
    {
        return match ($resourceType) {
            'stores' => Store::factory()->for($family)->create(),
            'store_sections' => StoreSection::factory()->for($family)->create(),
            'ingredients' => Ingredient::factory()->for($family)->create(),
            'recipe_tags' => RecipeTag::factory()->for($family)->create(),
            'recipes' => Recipe::factory()->for($family)->create(),
            'calendar_entries' => CalendarEntry::factory()->for($family)->create(),
        };
    }

    /** @return array{Family, string} */
    private function credential(): array
    {
        $issuer = User::factory()->create();
        $family = Family::factory()->create();
        FamilyMembership::factory()->create(['family_id' => $family->id, 'user_id' => $issuer->id]);
        $issued = app(IssueAgentCredential::class)->handle(
            new AuthorizedFamilyContext($issuer, $family),
            'OpenAPI example agent',
            [
                AgentCredentialAbility::CookbookWrite,
                AgentCredentialAbility::PlanningWrite,
                AgentCredentialAbility::DestructiveWrite,
            ],
        );

        return [$family, $issued->plainTextSecret];
    }
}
