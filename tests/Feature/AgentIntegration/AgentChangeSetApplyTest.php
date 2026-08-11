<?php

declare(strict_types=1);

namespace Tests\Feature\AgentIntegration;

use App\AgentIntegration\Actions\IssueAgentCredential;
use App\AgentIntegration\AgentCredentialAbility;
use App\AgentIntegration\Models\AgentChangeSet;
use App\Cookbook\Actions\AttachIngredientAlternative;
use App\Cookbook\Actions\AttachStoreSection;
use App\Cookbook\Actions\CreateRecipe;
use App\Cookbook\Actions\DetachStoreSection;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

final class AgentChangeSetApplyTest extends TestCase
{
    public function test_digest_bound_apply_persists_a_local_reference_graph_atomically_and_returns_complete_mappings(): void
    {
        [, , $secret] = $this->credential();
        $preview = $this->withToken($secret)->postJson('/api/v1/change-sets', $this->localGraphDocument())->assertCreated();

        $response = $this->withToken($secret)->postJson('/api/v1/change-sets/' . $preview->json('data.id') . '/apply', [
            'digest' => $preview->json('data.digest'),
            'warning_acknowledgements' => [],
        ])->assertOk();

        $response->assertJsonPath('data.status', 'applied')
            ->assertJsonPath('data.result.outcome', 'applied')
            ->assertJsonCount(6, 'data.identifier_mappings');
        foreach (['section', 'store', 'ingredient', 'tag', 'recipe', 'calendar'] as $localRef) {
            $this->assertIsInt($response->json("data.identifier_mappings.{$localRef}"));
        }

        $this->assertDatabaseCount('store_sections', 1);
        $this->assertDatabaseCount('stores', 1);
        $this->assertDatabaseCount('store_store_section', 1);
        $this->assertDatabaseCount('ingredients', 1);
        $this->assertDatabaseCount('recipe_tags', 1);
        $this->assertDatabaseCount('recipes', 1);
        $this->assertDatabaseCount('recipe_ingredients', 1);
        $this->assertDatabaseCount('recipe_steps', 1);
        $this->assertDatabaseCount('recipe_recipe_tag', 1);
        $this->assertDatabaseCount('calendar_entries', 1);
        $this->assertDatabaseHas('agent_change_sets', ['status' => 'applied', 'outcome' => 'applied']);
    }

    public function test_apply_uses_set_unset_and_wholesale_nested_replacement_for_every_update_handler(): void
    {
        [$context, $family, $secret] = $this->credential();
        $section = StoreSection::factory()->for($family)->create(['name' => 'Původní', 'colour' => '#111111', 'icon' => 'package']);
        $store = Store::factory()->for($family)->create(['name' => 'Starý obchod']);
        app(AttachStoreSection::class)->handle($context, $store->id, $section->id);
        $alternative = Ingredient::factory()->for($family)->create(['name' => 'Alternativa']);
        $ingredient = Ingredient::factory()->for($family)->create([
            'name' => 'Původní surovina',
            'description' => 'Původní popis',
            'store_id' => $store->id,
            'store_section_id' => $section->id,
            'piece_count' => '4',
        ]);
        $ingredient->nutritionProfile()->create([
            'family_id' => $family->id,
            'basis_kind' => 'grams',
            'basis_quantity' => '100',
            'energy_kcal' => '10',
            'fat_grams' => '1',
            'protein_grams' => '2',
            'carbohydrate_grams' => '3',
        ]);
        app(AttachIngredientAlternative::class)->handle($context, $ingredient->id, $alternative->id);
        $tag = RecipeTag::factory()->for($family)->create(['name' => 'Původní štítek']);
        $recipe = app(CreateRecipe::class)->handle($context, [
            'name' => 'Původní recept',
            'base_servings' => '4',
            'source_url' => 'https://example.test/old',
            'preparation_minutes' => 5,
            'cooking_minutes' => 10,
            'notes' => 'Původní poznámky',
            'ingredients' => [['ingredient_id' => $ingredient->id, 'quantity' => '1', 'quantity_kind' => 'piece']],
            'steps' => ['První krok'],
            'tag_ids' => [$tag->id],
            'nutrition' => null,
        ]);
        $entry = CalendarEntry::factory()->for($family)->create(['recipe_id' => $recipe->id, 'serving_count' => '1']);

        $operations = [
            $this->updateOperation('01-store', 'stores', $store, [
                'name' => 'Nový obchod',
                'store_section_ids' => [],
            ]),
            $this->updateOperation('02-section', 'store_sections', $section, [
                'name' => 'Nové oddělení',
                'colour' => '#22c55e',
                'icon' => 'carrot',
            ]),
            $this->updateOperation('03-ingredient', 'ingredients', $ingredient, [
                'name' => 'Nová surovina',
                'package_quantities' => ['weight_grams' => '500', 'volume_millilitres' => null, 'piece_count' => '6'],
                'alternative_ingredient_ids' => [],
            ], ['description', 'nutrition_profile', 'store_placement']),
            $this->updateOperation('04-tag', 'recipe_tags', $tag, ['name' => 'Nový štítek']),
            $this->updateOperation('05-recipe', 'recipes', $recipe, [
                'name' => 'Nový recept',
                'base_servings' => '6',
                'ingredients' => [['ingredient_id' => $ingredient->id, 'quantity' => '3', 'quantity_kind' => 'piece']],
                'steps' => [],
                'recipe_tag_ids' => [],
                'nutrition_override' => ['energy_kcal' => '100', 'fat_grams' => '2', 'protein_grams' => '3', 'carbohydrate_grams' => '4'],
            ], ['source_url', 'preparation_minutes', 'cooking_minutes', 'notes']),
            $this->updateOperation('06-calendar', 'calendar_entries', $entry, ['serving_count' => '2.5']),
        ];
        $preview = $this->withToken($secret)->postJson('/api/v1/change-sets', [
            'version' => 1,
            'client_request_id' => 'apply-all-updates',
            'operations' => $operations,
        ])->assertCreated();

        $this->withToken($secret)->postJson('/api/v1/change-sets/' . $preview->json('data.id') . '/apply', [
            'digest' => $preview->json('data.digest'),
            'warning_acknowledgements' => [],
        ])->assertOk()->assertJsonPath('data.status', 'applied');

        $this->assertSame('Nový obchod', $store->fresh()->name);
        $this->assertCount(0, $store->fresh()->storeSections);
        $this->assertSame('Nové oddělení', $section->fresh()->name);
        $this->assertSame('carrot', $section->fresh()->icon->value);
        $this->assertNull($ingredient->fresh()->description);
        $this->assertNull($ingredient->fresh()->store_id);
        $this->assertFalse($ingredient->fresh()->nutritionProfile()->exists());
        $this->assertDatabaseCount('ingredient_alternatives', 0);
        $this->assertSame('Nový štítek', $tag->fresh()->name);
        $this->assertSame('Nový recept', $recipe->fresh()->name);
        $this->assertSame('6.000000', $recipe->fresh()->base_servings);
        $this->assertSame('3.000000', $recipe->fresh()->ingredients()->sole()->quantity);
        $this->assertFalse($recipe->fresh()->steps()->exists());
        $this->assertFalse($recipe->fresh()->tags()->exists());
        $this->assertNull($recipe->fresh()->source_url);
        $this->assertSame('2.500000', $entry->fresh()->serving_count);
    }

    public function test_apply_binds_digest_and_warning_acknowledgements_and_is_idempotent_for_every_destructive_handler(): void
    {
        [, $family, $secret] = $this->credential();
        $store = Store::factory()->for($family)->create();
        $section = StoreSection::factory()->for($family)->create();
        $ingredientToArchive = Ingredient::factory()->for($family)->create();
        $ingredientToRestore = Ingredient::factory()->for($family)->create(['archived_at' => now()]);
        $tag = RecipeTag::factory()->for($family)->create();
        $recipeToArchive = Recipe::factory()->for($family)->create();
        $recipeToRestore = Recipe::factory()->for($family)->create(['archived_at' => now()]);
        $entry = CalendarEntry::factory()->for($family)->create();
        $warnings = [
            'calendar_entry_delete',
            'ingredient_archive',
            'recipe_archive',
            'recipe_tag_delete',
            'store_delete',
            'store_section_delete',
        ];
        $preview = $this->withToken($secret)->postJson('/api/v1/change-sets', [
            'version' => 1,
            'client_request_id' => 'apply-destructive-actions',
            'operations' => [
                $this->existingOperation('01-store-delete', 'stores', 'delete', $store),
                $this->existingOperation('02-section-delete', 'store_sections', 'delete', $section),
                $this->existingOperation('03-ingredient-archive', 'ingredients', 'archive', $ingredientToArchive),
                $this->existingOperation('04-ingredient-restore', 'ingredients', 'restore', $ingredientToRestore),
                $this->existingOperation('05-tag-delete', 'recipe_tags', 'delete', $tag),
                $this->existingOperation('06-recipe-archive', 'recipes', 'archive', $recipeToArchive),
                $this->existingOperation('07-recipe-restore', 'recipes', 'restore', $recipeToRestore),
                $this->existingOperation('08-calendar-delete', 'calendar_entries', 'delete', $entry),
            ],
        ])->assertCreated()->assertJsonPath('data.preview.warnings', $warnings);
        $applyUrl = '/api/v1/change-sets/' . $preview->json('data.id') . '/apply';

        $this->withToken($secret)->postJson($applyUrl, [
            'digest' => str_repeat('0', 64),
            'warning_acknowledgements' => $warnings,
        ])->assertConflict()->assertJsonPath('error.code', 'digest_mismatch');
        $this->withToken($secret)->postJson($applyUrl, [
            'digest' => $preview->json('data.digest'),
            'warning_acknowledgements' => [],
        ])->assertConflict()->assertJsonPath('error.code', 'warning_acknowledgement_mismatch');

        $first = $this->withToken($secret)->postJson($applyUrl, [
            'digest' => $preview->json('data.digest'),
            'warning_acknowledgements' => array_reverse($warnings),
        ])->assertOk()->assertJsonPath('data.status', 'applied');
        $retry = $this->withToken($secret)->postJson($applyUrl, [
            'digest' => $preview->json('data.digest'),
            'warning_acknowledgements' => $warnings,
        ])->assertOk();

        $this->assertSame($first->json('data.result'), $retry->json('data.result'));
        $this->assertModelMissing($store);
        $this->assertModelMissing($section);
        $this->assertNotNull($ingredientToArchive->fresh()->archived_at);
        $this->assertNull($ingredientToRestore->fresh()->archived_at);
        $this->assertModelMissing($tag);
        $this->assertNotNull($recipeToArchive->fresh()->archived_at);
        $this->assertNull($recipeToRestore->fresh()->archived_at);
        $this->assertModelMissing($entry);
    }

    public function test_expired_and_stale_previews_become_terminal_and_another_credential_cannot_apply_them(): void
    {
        Carbon::setTestNow('2026-08-11 12:00:00');
        [, $family, $secret] = $this->credential();
        $store = Store::factory()->for($family)->create(['name' => 'Původní']);
        $preview = $this->withToken($secret)->postJson('/api/v1/change-sets', [
            'version' => 1,
            'client_request_id' => 'stale-preview',
            'operations' => [$this->updateOperation('update-store', 'stores', $store, ['name' => 'Nový'])],
        ])->assertCreated();
        $applyUrl = '/api/v1/change-sets/' . $preview->json('data.id') . '/apply';

        [, , $otherSecret] = $this->credentialForFamily($family);
        Auth::forgetGuards();
        $this->withToken($otherSecret)->postJson($applyUrl, [
            'digest' => $preview->json('data.digest'),
            'warning_acknowledgements' => [],
        ])->assertNotFound()->assertJsonPath('error.code', 'family_scope_violation');

        Carbon::setTestNow('2026-08-11 12:00:02');
        $store->name = 'Mezitím změněný';
        $store->save();
        Auth::forgetGuards();
        $this->withToken($secret)->postJson($applyUrl, [
            'digest' => $preview->json('data.digest'),
            'warning_acknowledgements' => [],
        ])->assertConflict()->assertJsonPath('error.code', 'stale_preview');
        $this->assertDatabaseHas('agent_change_sets', ['id' => $preview->json('data.id'), 'status' => 'stale']);

        $expiredPreview = $this->withToken($secret)->postJson('/api/v1/change-sets', [
            'version' => 1,
            'client_request_id' => 'expired-preview',
            'operations' => [[
                'operation_id' => 'create-store',
                'resource_type' => 'stores',
                'action' => 'create',
                'local_ref' => 'store',
                'data' => ['name' => 'Budoucí obchod'],
            ]],
        ])->assertCreated();
        AgentChangeSet::query()->findOrFail($expiredPreview->json('data.id'))->forceFill(['expires_at' => now()->subSecond()])->save();
        $this->withToken($secret)->postJson('/api/v1/change-sets/' . $expiredPreview->json('data.id') . '/apply', [
            'digest' => $expiredPreview->json('data.digest'),
            'warning_acknowledgements' => [],
        ])->assertConflict()->assertJsonPath('error.code', 'preview_expired');
        $this->assertDatabaseHas('agent_change_sets', ['id' => $expiredPreview->json('data.id'), 'status' => 'expired']);
    }

    public function test_apply_rolls_back_every_prior_operation_and_keeps_retryable_domain_failures_previewed(): void
    {
        [$context, $family, $secret] = $this->credential();
        $store = Store::factory()->for($family)->create();
        $section = StoreSection::factory()->for($family)->create();
        app(AttachStoreSection::class)->handle($context, $store->id, $section->id);
        $document = [
            'version' => 1,
            'client_request_id' => 'atomic-retry',
            'operations' => [
                [
                    'operation_id' => '01-tag',
                    'resource_type' => 'recipe_tags',
                    'action' => 'create',
                    'local_ref' => 'tag',
                    'data' => ['name' => 'Dočasný štítek'],
                ],
                [
                    'operation_id' => '02-ingredient',
                    'resource_type' => 'ingredients',
                    'action' => 'create',
                    'local_ref' => 'ingredient',
                    'data' => [
                        'name' => 'Dočasná surovina',
                        'description' => null,
                        'package_quantities' => ['weight_grams' => null, 'volume_millilitres' => null, 'piece_count' => '4'],
                        'nutrition_profile' => null,
                        'store_placement' => ['store_id' => $store->id, 'store_section_id' => $section->id],
                        'alternative_ingredient_ids' => [],
                    ],
                ],
            ],
        ];
        $preview = $this->withToken($secret)->postJson('/api/v1/change-sets', $document)->assertCreated();
        $applyUrl = '/api/v1/change-sets/' . $preview->json('data.id') . '/apply';
        app(DetachStoreSection::class)->handle($context, $store->id, $section->id);

        $this->withToken($secret)->postJson($applyUrl, [
            'digest' => $preview->json('data.digest'),
            'warning_acknowledgements' => [],
        ])->assertUnprocessable()->assertJsonPath('error.code', 'validation_failed');
        $this->assertDatabaseMissing('recipe_tags', ['name' => 'Dočasný štítek']);
        $this->assertDatabaseMissing('ingredients', ['name' => 'Dočasná surovina']);
        $this->assertDatabaseHas('agent_change_sets', ['id' => $preview->json('data.id'), 'status' => 'previewed']);

        app(AttachStoreSection::class)->handle($context, $store->id, $section->id);
        $this->withToken($secret)->postJson($applyUrl, [
            'digest' => $preview->json('data.digest'),
            'warning_acknowledgements' => [],
        ])->assertOk()->assertJsonPath('data.status', 'applied');
        $this->assertDatabaseHas('recipe_tags', ['name' => 'Dočasný štítek']);
        $this->assertDatabaseHas('ingredients', ['name' => 'Dočasná surovina']);
    }

    /** @return array{AuthorizedFamilyContext, Family, string} */
    private function credential(): array
    {
        $issuer = User::factory()->create();
        $family = Family::factory()->create();
        FamilyMembership::factory()->create(['family_id' => $family->id, 'user_id' => $issuer->id]);
        $context = new AuthorizedFamilyContext($issuer, $family);
        $issued = app(IssueAgentCredential::class)->handle($context, 'Apply agent', [
            AgentCredentialAbility::CookbookWrite,
            AgentCredentialAbility::PlanningWrite,
            AgentCredentialAbility::DestructiveWrite,
        ]);

        return [$context, $family, $issued->plainTextSecret];
    }

    /** @return array{AuthorizedFamilyContext, Family, string} */
    private function credentialForFamily(Family $family): array
    {
        $issuer = User::factory()->create();
        FamilyMembership::factory()->create(['family_id' => $family->id, 'user_id' => $issuer->id]);
        $context = new AuthorizedFamilyContext($issuer, $family);
        $issued = app(IssueAgentCredential::class)->handle($context, 'Other apply agent', [
            AgentCredentialAbility::CookbookWrite,
            AgentCredentialAbility::PlanningWrite,
            AgentCredentialAbility::DestructiveWrite,
        ]);

        return [$context, $family, $issued->plainTextSecret];
    }

    /** @return array<string, mixed> */
    private function localGraphDocument(): array
    {
        return [
            'version' => 1,
            'client_request_id' => 'apply-local-graph',
            'operations' => [
                [
                    'operation_id' => '06-calendar',
                    'resource_type' => 'calendar_entries',
                    'action' => 'create',
                    'local_ref' => 'calendar',
                    'data' => [
                        'recipe_id' => ['local_ref' => 'recipe'],
                        'date' => '2026-08-20',
                        'meal_label' => 'večeře',
                        'serving_count' => '2',
                    ],
                ],
                [
                    'operation_id' => '05-recipe',
                    'resource_type' => 'recipes',
                    'action' => 'create',
                    'local_ref' => 'recipe',
                    'data' => [
                        'name' => 'Rajčatová polévka',
                        'base_servings' => '4',
                        'source_url' => 'https://example.test/recept',
                        'preparation_minutes' => 10,
                        'cooking_minutes' => 25,
                        'notes' => null,
                        'ingredients' => [[
                            'ingredient_id' => ['local_ref' => 'ingredient'],
                            'quantity' => '2',
                            'quantity_kind' => 'piece',
                        ]],
                        'steps' => ['Uvařit.'],
                        'recipe_tag_ids' => [['local_ref' => 'tag']],
                        'nutrition_override' => null,
                    ],
                ],
                [
                    'operation_id' => '04-tag',
                    'resource_type' => 'recipe_tags',
                    'action' => 'create',
                    'local_ref' => 'tag',
                    'data' => ['name' => 'Polévky'],
                ],
                [
                    'operation_id' => '03-ingredient',
                    'resource_type' => 'ingredients',
                    'action' => 'create',
                    'local_ref' => 'ingredient',
                    'data' => [
                        'name' => 'Rajčata',
                        'description' => null,
                        'package_quantities' => ['weight_grams' => '1000', 'volume_millilitres' => null, 'piece_count' => '8'],
                        'nutrition_profile' => null,
                        'store_placement' => [
                            'store_id' => ['local_ref' => 'store'],
                            'store_section_id' => ['local_ref' => 'section'],
                        ],
                        'alternative_ingredient_ids' => [],
                    ],
                ],
                [
                    'operation_id' => '02-store',
                    'resource_type' => 'stores',
                    'action' => 'create',
                    'local_ref' => 'store',
                    'data' => ['name' => 'Trh', 'store_section_ids' => [['local_ref' => 'section']]],
                ],
                [
                    'operation_id' => '01-section',
                    'resource_type' => 'store_sections',
                    'action' => 'create',
                    'local_ref' => 'section',
                    'data' => ['name' => 'Zelenina', 'colour' => '#22c55e', 'icon' => 'carrot'],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $set
     * @param  list<string>  $unset
     * @return array<string, mixed>
     */
    private function updateOperation(string $operationId, string $resourceType, Model $model, array $set, array $unset = []): array
    {
        return [
            'operation_id' => $operationId,
            'resource_type' => $resourceType,
            'action' => 'update',
            'resource_id' => $model->getKey(),
            'expected_updated_at' => $model->updated_at?->utc()->format('Y-m-d\TH:i:s\Z'),
            'set' => $set,
            'unset' => $unset,
        ];
    }

    /** @return array<string, mixed> */
    private function existingOperation(string $operationId, string $resourceType, string $action, Model $model): array
    {
        return [
            'operation_id' => $operationId,
            'resource_type' => $resourceType,
            'action' => $action,
            'resource_id' => $model->getKey(),
            'expected_updated_at' => $model->updated_at?->utc()->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
