<?php

declare(strict_types=1);

namespace Tests\Feature\AgentIntegration;

use App\AgentIntegration\Actions\IssueAgentCredential;
use App\AgentIntegration\Actions\RotateAgentCredential;
use App\AgentIntegration\AgentCredentialAbility;
use App\AgentIntegration\Models\AgentChangeSet;
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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

final class AgentChangeSetPreviewTest extends TestCase
{
    public function test_valid_preview_is_side_effect_free_canonical_and_persisted_for_24_hours(): void
    {
        Carbon::setTestNow('2026-08-11 12:00:00');
        [, , $secret] = $this->credential();
        $document = [
            'version' => 1,
            'client_request_id' => 'request-001',
            'title' => 'Doplnění obchodů',
            'source_urls' => ['https://example.test/source'],
            'note' => 'Importováno agentem.',
            'operations' => [
                [
                    'operation_id' => 'store-b',
                    'resource_type' => 'stores',
                    'action' => 'create',
                    'local_ref' => 'second-store',
                    'data' => ['name' => 'Večerní trh'],
                ],
                [
                    'operation_id' => 'store-a',
                    'resource_type' => 'stores',
                    'action' => 'create',
                    'local_ref' => 'first-store',
                    'data' => ['name' => 'Farmářský trh'],
                ],
            ],
        ];

        $response = $this->withToken($secret)->postJson('/api/v1/change-sets', $document)->assertCreated();

        $response->assertJsonPath('data.status', 'previewed')
            ->assertJsonPath('data.canonical_request.operations.0.operation_id', 'store-a')
            ->assertJsonPath('data.canonical_request.operations.1.operation_id', 'store-b')
            ->assertJsonPath('data.preview.effects.0.operation_id', 'store-a')
            ->assertJsonPath('data.preview.effects.1.operation_id', 'store-b')
            ->assertJsonPath('data.preview.warnings', []);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $response->json('data.digest'));
        $this->assertDatabaseCount('stores', 0);

        $changeSet = AgentChangeSet::query()->sole();
        $this->assertSame($response->json('data.id'), $changeSet->id);
        $this->assertSame($response->json('data.digest'), $changeSet->digest);
        $this->assertSame(2, $changeSet->operation_count);
        $this->assertSame(['stores'], $changeSet->resource_types);
        $this->assertTrue($changeSet->expires_at->equalTo(now()->addHours(24)));
    }

    public function test_preview_idempotency_is_credential_scoped_and_conflicting_reuse_is_rejected(): void
    {
        [, , $secret] = $this->credential();
        $document = $this->storeCreateDocument('request-idempotent', 'Trh');

        $first = $this->withToken($secret)->postJson('/api/v1/change-sets', $document)->assertCreated();
        $retry = $this->withToken($secret)->postJson('/api/v1/change-sets', $document)->assertOk();

        $this->assertSame($first->json('data.id'), $retry->json('data.id'));
        $this->assertSame($first->json('data.digest'), $retry->json('data.digest'));
        $this->assertDatabaseCount('agent_change_sets', 1);

        $document['operations'][0]['data']['name'] = 'Jiný trh';
        $this->withToken($secret)->postJson('/api/v1/change-sets', $document)
            ->assertConflict()
            ->assertJsonPath('error.code', 'idempotency_conflict')
            ->assertJsonPath('error.retryable', false);
        $this->assertDatabaseCount('agent_change_sets', 1);

        [, , $otherSecret] = $this->credential();
        Auth::forgetGuards();
        $this->withToken($otherSecret)->postJson('/api/v1/change-sets', $document)->assertCreated();
        $this->assertDatabaseCount('agent_change_sets', 2);
    }

    public function test_new_preview_retains_family_scoped_supersession_lineage_without_mutating_the_original(): void
    {
        [, $family, $secret] = $this->credential();
        $original = $this->withToken($secret)->postJson(
            '/api/v1/change-sets',
            $this->storeCreateDocument('lineage-original', 'Původní trh'),
        )->assertCreated();
        $replacementDocument = $this->storeCreateDocument('lineage-replacement', 'Náhradní trh');
        $replacementDocument['supersedes_id'] = $original->json('data.id');

        $replacement = $this->withToken($secret)->postJson('/api/v1/change-sets', $replacementDocument)
            ->assertCreated()
            ->assertJsonPath('data.supersedes_id', $original->json('data.id'));

        $this->assertDatabaseHas('agent_change_sets', [
            'id' => $original->json('data.id'),
            'status' => 'previewed',
            'supersedes_id' => null,
        ]);
        $this->assertDatabaseHas('agent_change_sets', [
            'id' => $replacement->json('data.id'),
            'supersedes_id' => $original->json('data.id'),
        ]);

        $foreign = AgentChangeSet::factory()->for(Family::factory()->create())->create();
        $foreignDocument = $this->storeCreateDocument('lineage-foreign', 'Cizí trh');
        $foreignDocument['supersedes_id'] = $foreign->id;
        $this->withToken($secret)->postJson('/api/v1/change-sets', $foreignDocument)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'family_scope_violation');

        $this->assertSame(2, AgentChangeSet::query()->where('family_id', $family->id)->count());
    }

    public function test_invalid_preview_and_name_conflict_return_structured_errors_without_persistence(): void
    {
        [, $family, $secret] = $this->credential();

        $this->withToken($secret)->postJson('/api/v1/change-sets', [
            'version' => 1,
            'client_request_id' => 'invalid-request',
            'operations' => [],
        ])->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonPath('error.path', '/operations');
        $this->assertDatabaseCount('agent_change_sets', 0);

        Store::factory()->for($family)->create(['name' => 'Farmářský trh']);
        $this->withToken($secret)->postJson(
            '/api/v1/change-sets',
            $this->storeCreateDocument('name-conflict', '  FARMÁŘSKÝ   TRH '),
        )->assertConflict()
            ->assertJsonPath('error.code', 'name_conflict')
            ->assertJsonPath('error.operation_id', 'create-store');
        $this->assertDatabaseCount('agent_change_sets', 0);
    }

    public function test_empty_create_data_is_treated_as_an_object_and_reports_the_first_missing_resource_field(): void
    {
        [, , $secret] = $this->credential();

        $this->withToken($secret)->postJson('/api/v1/change-sets', [
            'version' => 1,
            'client_request_id' => 'empty-create-data',
            'operations' => [[
                'operation_id' => 'create-store',
                'resource_type' => 'stores',
                'action' => 'create',
                'local_ref' => 'store',
                'data' => (object) [],
            ]],
        ])->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonPath('error.path', '/operations/0/data/name')
            ->assertJsonPath('error.operation_id', 'create-store');

        $this->assertDatabaseCount('agent_change_sets', 0);
    }

    public function test_operation_payload_and_per_credential_rate_limits_are_enforced(): void
    {
        [, , $secret] = $this->credential();
        $document = $this->storeCreateDocument('too-many', 'Trh');
        $document['operations'] = array_fill(0, 251, $document['operations'][0]);

        $this->withToken($secret)->postJson('/api/v1/change-sets', $document)
            ->assertStatus(413)
            ->assertJsonPath('error.code', 'payload_limit_exceeded')
            ->assertJsonPath('error.details.max_operations', 250);
        $this->assertDatabaseCount('agent_change_sets', 0);

        $oversized = $this->storeCreateDocument('too-large', 'Trh');
        $oversized['note'] = str_repeat('a', 2 * 1024 * 1024);
        $this->withToken($secret)->postJson('/api/v1/change-sets', $oversized)
            ->assertStatus(413)
            ->assertJsonPath('error.code', 'payload_limit_exceeded')
            ->assertJsonPath('error.details.max_payload_bytes', 2 * 1024 * 1024);

        [, , $rateSecret] = $this->credential();
        Auth::forgetGuards();
        config(['agent-integration.rates.preview_per_minute' => 2]);
        foreach (['rate-1', 'rate-2'] as $requestId) {
            $this->withToken($rateSecret)->postJson(
                '/api/v1/change-sets',
                $this->storeCreateDocument($requestId, $requestId),
            )->assertCreated();
        }
        $this->withToken($rateSecret)->postJson(
            '/api/v1/change-sets',
            $this->storeCreateDocument('rate-3', 'rate-3'),
        )->assertTooManyRequests()
            ->assertHeader('Retry-After')
            ->assertJsonPath('error.code', 'rate_limit_exceeded')
            ->assertJsonPath('error.retryable', true);
    }

    public function test_rotation_invalidates_previews_and_scheduled_cleanup_expires_then_removes_them(): void
    {
        Carbon::setTestNow('2026-08-11 12:00:00');
        [$context, , $secret] = $this->credential();
        $this->withToken($secret)->postJson(
            '/api/v1/change-sets',
            $this->storeCreateDocument('rotation', 'Trh'),
        )->assertCreated();
        $credentialId = AgentChangeSet::query()->sole()->agent_credential_id;

        app(RotateAgentCredential::class)->handle($context, $credentialId);

        $invalidated = AgentChangeSet::query()->sole();
        $this->assertSame('invalidated', $invalidated->status);
        $this->assertTrue($invalidated->terminal_at?->equalTo(now()) ?? false);

        $invalidated->forceFill([
            'status' => 'previewed',
            'terminal_at' => null,
            'expires_at' => now()->subSecond(),
        ])->save();
        $this->artisan('agent-change-sets:cleanup')->assertSuccessful();
        $expired = AgentChangeSet::query()->sole();
        $this->assertSame('expired', $expired->status);
        $this->assertTrue($expired->terminal_at?->equalTo(now()) ?? false);

        Carbon::setTestNow(now()->addHours(25));
        $this->artisan('agent-change-sets:cleanup')->assertSuccessful();
        $this->assertDatabaseCount('agent_change_sets', 0);
    }

    public function test_preview_resolves_cross_resource_local_references_in_dependency_order_without_writes(): void
    {
        [, , $secret] = $this->credential();

        $response = $this->withToken($secret)->postJson('/api/v1/change-sets', [
            'version' => 1,
            'client_request_id' => 'local-reference-graph',
            'operations' => [
                [
                    'operation_id' => 'f-calendar',
                    'resource_type' => 'calendar_entries',
                    'action' => 'create',
                    'local_ref' => 'calendar',
                    'data' => [
                        'recipe_id' => ['local_ref' => 'recipe'],
                        'date' => '2026-08-20',
                        'meal_label' => 'večeře',
                        'serving_count' => '02.5000',
                    ],
                ],
                [
                    'operation_id' => 'e-recipe',
                    'resource_type' => 'recipes',
                    'action' => 'create',
                    'local_ref' => 'recipe',
                    'data' => [
                        'name' => 'Rajčatová polévka',
                        'base_servings' => '04.000',
                        'source_url' => 'https://example.test/recept',
                        'preparation_minutes' => 10,
                        'cooking_minutes' => 25,
                        'notes' => 'Podávat teplé.',
                        'ingredients' => [[
                            'ingredient_id' => ['local_ref' => 'ingredient'],
                            'quantity' => '001.2500',
                            'quantity_kind' => 'piece',
                        ]],
                        'steps' => ['Uvařit.'],
                        'recipe_tag_ids' => [['local_ref' => 'tag']],
                        'nutrition_override' => null,
                    ],
                ],
                [
                    'operation_id' => 'd-tag',
                    'resource_type' => 'recipe_tags',
                    'action' => 'create',
                    'local_ref' => 'tag',
                    'data' => ['name' => 'Polévky'],
                ],
                [
                    'operation_id' => 'c-ingredient',
                    'resource_type' => 'ingredients',
                    'action' => 'create',
                    'local_ref' => 'ingredient',
                    'data' => [
                        'name' => 'Rajčata',
                        'description' => 'Zralá rajčata',
                        'package_quantities' => [
                            'weight_grams' => '01000.000',
                            'volume_millilitres' => null,
                            'piece_count' => '08.00',
                        ],
                        'nutrition_profile' => null,
                        'store_placement' => [
                            'store_id' => ['local_ref' => 'store'],
                            'store_section_id' => ['local_ref' => 'section'],
                        ],
                        'alternative_ingredient_ids' => [],
                    ],
                ],
                [
                    'operation_id' => 'b-store',
                    'resource_type' => 'stores',
                    'action' => 'create',
                    'local_ref' => 'store',
                    'data' => [
                        'name' => 'Trh',
                        'store_section_ids' => [['local_ref' => 'section']],
                    ],
                ],
                [
                    'operation_id' => 'a-section',
                    'resource_type' => 'store_sections',
                    'action' => 'create',
                    'local_ref' => 'section',
                    'data' => ['name' => 'Zelenina', 'colour' => '#22c55e', 'icon' => 'carrot'],
                ],
            ],
        ])->assertCreated();

        $response->assertJsonPath('data.preview.execution_order', [
            'a-section',
            'b-store',
            'c-ingredient',
            'd-tag',
            'e-recipe',
            'f-calendar',
        ])->assertJsonPath('data.canonical_request.operations.2.data.package_quantities.weight_grams', '1000')
            ->assertJsonPath('data.canonical_request.operations.4.data.ingredients.0.quantity', '1.25')
            ->assertJsonPath('data.canonical_request.operations.5.data.serving_count', '2.5');

        $this->assertDatabaseCount('stores', 0);
        $this->assertDatabaseCount('store_sections', 0);
        $this->assertDatabaseCount('ingredients', 0);
        $this->assertDatabaseCount('recipe_tags', 0);
        $this->assertDatabaseCount('recipes', 0);
        $this->assertDatabaseCount('calendar_entries', 0);
    }

    public function test_preview_supports_every_existing_resource_action_and_returns_stable_consequence_warnings(): void
    {
        [, $family, $secret] = $this->credential();
        $storeToUpdate = Store::factory()->for($family)->create();
        $storeToDelete = Store::factory()->for($family)->create();
        $sectionToUpdate = StoreSection::factory()->for($family)->create();
        $sectionToDelete = StoreSection::factory()->for($family)->create();
        $ingredientToUpdate = Ingredient::factory()->for($family)->create();
        $ingredientToArchive = Ingredient::factory()->for($family)->create();
        $ingredientToRestore = Ingredient::factory()->for($family)->create(['archived_at' => now()]);
        $tagToUpdate = RecipeTag::factory()->for($family)->create();
        $tagToDelete = RecipeTag::factory()->for($family)->create();
        $recipeToUpdate = Recipe::factory()->for($family)->create();
        $recipeToArchive = Recipe::factory()->for($family)->create();
        $recipeToRestore = Recipe::factory()->for($family)->create(['archived_at' => now()]);
        $calendarToUpdate = CalendarEntry::factory()->for($family)->create();
        $calendarToDelete = CalendarEntry::factory()->for($family)->create();

        $operations = [
            $this->existingOperation('01-store-update', 'stores', 'update', $storeToUpdate, ['set' => ['name' => 'Nový obchod']]),
            $this->existingOperation('02-store-delete', 'stores', 'delete', $storeToDelete),
            $this->existingOperation('03-section-update', 'store_sections', 'update', $sectionToUpdate, ['set' => ['name' => 'Nové oddělení']]),
            $this->existingOperation('04-section-delete', 'store_sections', 'delete', $sectionToDelete),
            $this->existingOperation('05-ingredient-update', 'ingredients', 'update', $ingredientToUpdate, ['set' => ['description' => 'Nový popis']]),
            $this->existingOperation('06-ingredient-archive', 'ingredients', 'archive', $ingredientToArchive),
            $this->existingOperation('07-ingredient-restore', 'ingredients', 'restore', $ingredientToRestore),
            $this->existingOperation('08-tag-update', 'recipe_tags', 'update', $tagToUpdate, ['set' => ['name' => 'Nový štítek']]),
            $this->existingOperation('09-tag-delete', 'recipe_tags', 'delete', $tagToDelete),
            $this->existingOperation('10-recipe-update', 'recipes', 'update', $recipeToUpdate, ['set' => ['notes' => 'Nová poznámka']]),
            $this->existingOperation('11-recipe-archive', 'recipes', 'archive', $recipeToArchive),
            $this->existingOperation('12-recipe-restore', 'recipes', 'restore', $recipeToRestore),
            $this->existingOperation('13-calendar-update', 'calendar_entries', 'update', $calendarToUpdate, ['set' => ['serving_count' => '2']]),
            $this->existingOperation('14-calendar-delete', 'calendar_entries', 'delete', $calendarToDelete),
        ];

        $response = $this->withToken($secret)->postJson('/api/v1/change-sets', [
            'version' => 1,
            'client_request_id' => 'every-existing-action',
            'operations' => $operations,
        ])->assertCreated();

        $response->assertJsonPath('data.preview.warnings', [
            'calendar_entry_delete',
            'ingredient_archive',
            'recipe_archive',
            'recipe_tag_delete',
            'store_delete',
            'store_section_delete',
        ]);
        $this->assertCount(14, $response->json('data.preview.effects'));
        $this->assertDatabaseCount('stores', 2);
        $this->assertSame($storeToUpdate->name, $storeToUpdate->fresh()->name);
        $this->assertDatabaseHas('calendar_entries', ['id' => $calendarToDelete->id]);
    }

    public function test_preview_enforces_the_operation_ability_matrix_without_persistence(): void
    {
        [, , $readOnlySecret] = $this->credential([]);
        $this->withToken($readOnlySecret)->postJson('/api/v1/change-sets', $this->storeCreateDocument('read-only', 'Trh'))
            ->assertForbidden()
            ->assertJsonPath('error.code', 'ability_required')
            ->assertJsonPath('error.details.required_abilities.0', 'cookbook:write');

        [, $family, $cookbookSecret] = $this->credential([AgentCredentialAbility::CookbookWrite]);
        Auth::forgetGuards();
        $recipe = Recipe::factory()->for($family)->create();
        $this->withToken($cookbookSecret)->postJson('/api/v1/change-sets', [
            'version' => 1,
            'client_request_id' => 'planning-missing',
            'operations' => [[
                'operation_id' => 'calendar',
                'resource_type' => 'calendar_entries',
                'action' => 'create',
                'local_ref' => 'calendar',
                'data' => ['recipe_id' => $recipe->id, 'date' => '2026-08-20', 'meal_label' => null, 'serving_count' => '1'],
            ]],
        ])->assertForbidden()
            ->assertJsonPath('error.details.required_abilities.0', 'planning:write');

        $ingredient = Ingredient::factory()->for($family)->create();
        $this->withToken($cookbookSecret)->postJson('/api/v1/change-sets', [
            'version' => 1,
            'client_request_id' => 'destructive-missing',
            'operations' => [$this->existingOperation('archive', 'ingredients', 'archive', $ingredient)],
        ])->assertForbidden()
            ->assertJsonPath('error.details.required_abilities.0', 'destructive:write');
        $this->assertDatabaseCount('agent_change_sets', 0);
    }

    public function test_preview_rejects_foreign_wrong_type_unknown_and_cyclic_relationship_references_without_persistence(): void
    {
        [, $family, $secret] = $this->credential();
        $foreignFamily = Family::factory()->create();
        $foreignIngredient = Ingredient::factory()->for($foreignFamily)->create();
        $recipe = $this->minimalRecipeCreateOperation($foreignIngredient->id);

        $this->withToken($secret)->postJson('/api/v1/change-sets', [
            'version' => 1,
            'client_request_id' => 'foreign-reference',
            'operations' => [$recipe],
        ])->assertNotFound()
            ->assertJsonPath('error.code', 'family_scope_violation')
            ->assertJsonPath('error.path', '/operations/0/data/ingredients/0/ingredient_id');

        $recipe['data']['ingredients'][0]['ingredient_id'] = ['local_ref' => 'tag'];
        $this->withToken($secret)->postJson('/api/v1/change-sets', [
            'version' => 1,
            'client_request_id' => 'wrong-local-type',
            'operations' => [
                $recipe,
                [
                    'operation_id' => 'tag',
                    'resource_type' => 'recipe_tags',
                    'action' => 'create',
                    'local_ref' => 'tag',
                    'data' => ['name' => 'Tag'],
                ],
            ],
        ])->assertUnprocessable()->assertJsonPath('error.code', 'local_reference_type_mismatch');

        $recipe['data']['ingredients'][0]['ingredient_id'] = ['local_ref' => 'missing'];
        $this->withToken($secret)->postJson('/api/v1/change-sets', [
            'version' => 1,
            'client_request_id' => 'unknown-local-reference',
            'operations' => [$recipe],
        ])->assertUnprocessable()->assertJsonPath('error.code', 'local_reference_not_found');

        $this->withToken($secret)->postJson('/api/v1/change-sets', [
            'version' => 1,
            'client_request_id' => 'dependency-cycle',
            'operations' => [
                $this->ingredientCreateOperation('first', 'První', [['local_ref' => 'second']]),
                $this->ingredientCreateOperation('second', 'Druhá', [['local_ref' => 'first']]),
            ],
        ])->assertUnprocessable()->assertJsonPath('error.code', 'dependency_cycle');

        $this->assertDatabaseCount('agent_change_sets', 0);
        $this->assertDatabaseCount('recipes', 0);
        $this->assertDatabaseMissing('ingredients', ['family_id' => $family->id]);
    }

    public function test_preview_rejects_invalid_aggregate_values_unknown_fields_and_normalized_update_conflicts_without_persistence(): void
    {
        [, $family, $secret] = $this->credential();
        $invalidIngredient = $this->ingredientCreateOperation('ingredient', 'Surovina', []);
        $invalidIngredient['data']['package_quantities'] = ['weight_grams' => null, 'volume_millilitres' => null, 'piece_count' => null];
        $this->withToken($secret)->postJson('/api/v1/change-sets', [
            'version' => 1,
            'client_request_id' => 'empty-package',
            'operations' => [$invalidIngredient],
        ])->assertUnprocessable()->assertJsonPath('error.code', 'validation_failed');

        $recipe = $this->minimalRecipeCreateOperation(1);
        $recipe['data']['ingredients'] = [];
        $this->withToken($secret)->postJson('/api/v1/change-sets', [
            'version' => 1,
            'client_request_id' => 'empty-recipe',
            'operations' => [$recipe],
        ])->assertUnprocessable()->assertJsonPath('error.path', '/operations/0/data/ingredients');

        $calendar = [
            'operation_id' => 'calendar',
            'resource_type' => 'calendar_entries',
            'action' => 'create',
            'local_ref' => 'calendar',
            'data' => ['recipe_id' => ['local_ref' => 'recipe'], 'date' => '2026-02-30', 'meal_label' => null, 'serving_count' => '-1'],
        ];
        $recipe = $this->minimalRecipeCreateOperation(['local_ref' => 'ingredient']);
        $ingredient = $this->ingredientCreateOperation('ingredient', 'Surovina', []);
        $this->withToken($secret)->postJson('/api/v1/change-sets', [
            'version' => 1,
            'client_request_id' => 'invalid-calendar',
            'operations' => [$calendar, $recipe, $ingredient],
        ])->assertUnprocessable()->assertJsonPath('error.code', 'validation_failed');

        $existing = Store::factory()->for($family)->create(['name' => 'Existující']);
        $renamed = Store::factory()->for($family)->create(['name' => 'Jiný']);
        $operation = $this->existingOperation('rename', 'stores', 'update', $renamed, [
            'set' => ['name' => '  EXISTUJÍCÍ  ', 'unknown_field' => 'x'],
        ]);
        $this->withToken($secret)->postJson('/api/v1/change-sets', [
            'version' => 1,
            'client_request_id' => 'unknown-update-field',
            'operations' => [$operation],
        ])->assertUnprocessable()->assertJsonPath('error.code', 'validation_failed');
        unset($operation['set']['unknown_field']);
        $this->withToken($secret)->postJson('/api/v1/change-sets', [
            'version' => 1,
            'client_request_id' => 'normalized-update-conflict',
            'operations' => [$operation],
        ])->assertConflict()->assertJsonPath('error.code', 'name_conflict');

        $this->assertSame('Existující', $existing->fresh()->name);
        $this->assertDatabaseCount('agent_change_sets', 0);
    }

    /**
     * @param  list<AgentCredentialAbility>|null  $abilities
     * @return array{AuthorizedFamilyContext, Family, string}
     */
    private function credential(?array $abilities = null): array
    {
        $issuer = User::factory()->create();
        $family = Family::factory()->create();
        FamilyMembership::factory()->create(['family_id' => $family->id, 'user_id' => $issuer->id]);
        $context = new AuthorizedFamilyContext($issuer, $family);
        $issued = app(IssueAgentCredential::class)->handle($context, 'Change Set agent', $abilities ?? [
            AgentCredentialAbility::CookbookWrite,
            AgentCredentialAbility::PlanningWrite,
            AgentCredentialAbility::DestructiveWrite,
        ]);

        return [$context, $family, $issued->plainTextSecret];
    }

    /** @return array<string, mixed> */
    private function storeCreateDocument(string $requestId, string $name): array
    {
        return [
            'version' => 1,
            'client_request_id' => $requestId,
            'operations' => [[
                'operation_id' => 'create-store',
                'resource_type' => 'stores',
                'action' => 'create',
                'local_ref' => 'new-store',
                'data' => ['name' => $name],
            ]],
        ];
    }

    /** @return array<string, mixed> */
    private function minimalRecipeCreateOperation(mixed $ingredientReference): array
    {
        return [
            'operation_id' => 'recipe',
            'resource_type' => 'recipes',
            'action' => 'create',
            'local_ref' => 'recipe',
            'data' => [
                'name' => 'Recept',
                'base_servings' => '2',
                'source_url' => null,
                'preparation_minutes' => null,
                'cooking_minutes' => null,
                'notes' => null,
                'ingredients' => [['ingredient_id' => $ingredientReference, 'quantity' => '1', 'quantity_kind' => 'piece']],
                'steps' => [],
                'recipe_tag_ids' => [],
                'nutrition_override' => null,
            ],
        ];
    }

    /** @param list<mixed> $alternatives @return array<string, mixed> */
    private function ingredientCreateOperation(string $localRef, string $name, array $alternatives): array
    {
        return [
            'operation_id' => $localRef,
            'resource_type' => 'ingredients',
            'action' => 'create',
            'local_ref' => $localRef,
            'data' => [
                'name' => $name,
                'description' => null,
                'package_quantities' => ['weight_grams' => null, 'volume_millilitres' => null, 'piece_count' => '1'],
                'nutrition_profile' => null,
                'store_placement' => ['store_id' => null, 'store_section_id' => null],
                'alternative_ingredient_ids' => $alternatives,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function existingOperation(string $operationId, string $resourceType, string $action, object $model, array $extra = []): array
    {
        /** @var mixed $id */
        $id = $model->id;
        /** @var mixed $updatedAt */
        $updatedAt = $model->updated_at;

        return [
            'operation_id' => $operationId,
            'resource_type' => $resourceType,
            'action' => $action,
            'resource_id' => $id,
            'expected_updated_at' => $updatedAt->utc()->format('Y-m-d\TH:i:s\Z'),
            ...$extra,
        ];
    }
}
