<?php

declare(strict_types=1);

namespace Tests\Feature\AgentIntegration;

use App\AgentIntegration\Actions\IssueAgentCredential;
use App\AgentIntegration\Models\AgentCredential;
use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\IngredientNutritionProfile;
use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\RecipeIngredient;
use App\Cookbook\Models\RecipeStep;
use App\Cookbook\Models\RecipeTag;
use App\Cookbook\Models\Store;
use App\Cookbook\Models\StoreSection;
use App\FamilyAccess\AuthorizedFamilyContext;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\MealPlanning\Models\CalendarEntry;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class FamilyCatalogTest extends TestCase
{
    public function test_catalog_requires_a_live_content_read_agent_credential(): void
    {
        $this->getJson('/api/v1/catalog')->assertUnauthorized()->assertExactJson([
            'error' => [
                'code' => 'authentication_required',
                'message' => 'A valid Agent Credential is required.',
                'path' => null,
                'operation_id' => null,
                'details' => [],
                'retryable' => false,
            ],
        ]);

        [$issuer, $family, , $secret] = $this->credentialContext();
        $credential = AgentCredential::query()->sole();
        $credential->forceFill(['abilities' => []])->save();

        $abilityResponse = $this->withToken($secret)->getJson('/api/v1/catalog')->assertForbidden();
        $this->assertSame('ability_required', $abilityResponse->json('error.code'), $abilityResponse->content());

        $credential->forceFill(['abilities' => ['content:read'], 'expires_at' => now()->subSecond()])->save();
        Auth::forgetGuards();
        $this->withToken($secret)->getJson('/api/v1/catalog')->assertUnauthorized();

        $this->assertTrue(FamilyMembership::query()
            ->whereBelongsTo($issuer)
            ->whereBelongsTo($family)
            ->exists());
    }

    public function test_catalog_rejects_web_sessions_revoked_credentials_and_issuer_membership_loss(): void
    {
        [$issuer, $family, , $secret] = $this->credentialContext();
        $credential = AgentCredential::query()->sole();

        $this->actingAs($issuer)->getJson('/api/v1/catalog')->assertUnauthorized();

        Auth::guard('web')->logout();
        Auth::forgetGuards();
        $credential->forceFill(['revoked_at' => now()])->save();
        $this->withToken($secret)->getJson('/api/v1/catalog')->assertUnauthorized();

        $credential->forceFill(['revoked_at' => null])->save();
        FamilyMembership::query()
            ->whereBelongsTo($issuer)
            ->whereBelongsTo($family)
            ->delete();
        Auth::forgetGuards();
        $this->withToken($secret)->getJson('/api/v1/catalog')->assertUnauthorized();
        $this->assertDatabaseHas('agent_credentials', ['id' => $credential->id]);
    }

    public function test_catalog_is_complete_deterministic_and_credential_family_scoped_without_changing_current_family(): void
    {
        [$issuer, $family, $currentFamily, $secret] = $this->credentialContext();
        $resources = $this->createCompleteCatalog($family);
        Store::factory()->for($currentFamily)->create(['name' => 'Soukromý obchod']);

        $response = $this->withToken($secret)->getJson('/api/v1/catalog')->assertOk();

        $response->assertJsonPath('meta.count', 7)
            ->assertJsonPath('data.0.resource_type', 'stores')
            ->assertJsonPath('data.1.resource_type', 'store_sections')
            ->assertJsonPath('data.2.resource_type', 'ingredients')
            ->assertJsonPath('data.4.resource_type', 'recipe_tags')
            ->assertJsonPath('data.5.resource_type', 'recipes')
            ->assertJsonPath('data.6.resource_type', 'calendar_entries')
            ->assertJsonPath('data.0.store_sections.0.store_section_id', $resources['section']->id)
            ->assertJsonPath('data.0.store_sections.0.position', 0)
            ->assertJsonPath('data.1.store_ids.0', $resources['store']->id)
            ->assertJsonPath('data.4.recipe_ids.0', $resources['recipe']->id)
            ->assertJsonMissing(['name' => 'Soukromý obchod']);

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/',
            $response->json('data.0.updated_at'),
        );
        $response->assertJsonMissing(['family_id' => $family->id]);

        $issuer->refresh();
        $this->assertSame($currentFamily->id, $issuer->current_family_id);

        $catalogRecipe = collect($response->json('data'))->firstWhere('resource_type', 'recipes');
        $this->withToken($secret)
            ->getJson('/api/v1/catalog/recipes/' . $resources['recipe']->id)
            ->assertOk()
            ->assertExactJson(['data' => $catalogRecipe]);
    }

    public function test_catalog_serializes_exact_ingredient_recipe_and_calendar_aggregates(): void
    {
        [, $family, , $secret] = $this->credentialContext();
        $resources = $this->createCompleteCatalog($family);

        $this->withToken($secret)->getJson('/api/v1/catalog/ingredients/' . $resources['ingredient']->id)
            ->assertOk()
            ->assertJsonPath('data.resource_type', 'ingredients')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.name', 'Mléko')
            ->assertJsonPath('data.normalized_name', 'mléko')
            ->assertJsonPath('data.package_quantities.weight_grams', '1000.000000')
            ->assertJsonPath('data.package_quantities.volume_millilitres', '750.250000')
            ->assertJsonPath('data.package_quantities.piece_count', '2.500000')
            ->assertJsonPath('data.store_placement.store_id', $resources['store']->id)
            ->assertJsonPath('data.store_placement.store_section_id', $resources['section']->id)
            ->assertJsonPath('data.nutrition_profile.basis_kind', 'millilitres')
            ->assertJsonPath('data.nutrition_profile.basis_quantity', '100.000000')
            ->assertJsonPath('data.nutrition_profile.energy_kcal', '42.125000')
            ->assertJsonPath('data.alternative_ingredient_ids.0', $resources['alternative']->id);

        $this->withToken($secret)->getJson('/api/v1/catalog/recipes/' . $resources['recipe']->id)
            ->assertOk()
            ->assertJsonPath('data.base_servings', '4.500000')
            ->assertJsonPath('data.source_url', 'https://example.test/recept')
            ->assertJsonPath('data.preparation_minutes', 15)
            ->assertJsonPath('data.cooking_minutes', 30)
            ->assertJsonPath('data.ingredients.0.ingredient_id', $resources['ingredient']->id)
            ->assertJsonPath('data.ingredients.0.quantity', '125.750000')
            ->assertJsonPath('data.ingredients.0.quantity_kind', 'millilitres')
            ->assertJsonPath('data.steps.0.instruction', 'Promíchejte.')
            ->assertJsonPath('data.recipe_tag_ids.0', $resources['tag']->id)
            ->assertJsonPath('data.nutrition_override.energy_kcal', '510.500000');

        $this->withToken($secret)->getJson('/api/v1/catalog/calendar_entries/' . $resources['calendar']->id)
            ->assertOk()
            ->assertJsonPath('data.date', '2026-08-15')
            ->assertJsonPath('data.meal_label', 'oběd')
            ->assertJsonPath('data.serving_count', '2.250000')
            ->assertJsonPath('data.recipe_id', $resources['recipe']->id);
    }

    public function test_catalog_filters_resource_type_and_status_and_hides_other_family_details(): void
    {
        [, $family, $otherFamily, $secret] = $this->credentialContext();
        $resources = $this->createCompleteCatalog($family);
        $otherRecipe = Recipe::factory()->for($otherFamily)->create();

        $this->withToken($secret)
            ->getJson('/api/v1/catalog?resource_type=ingredients&status=archived')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $resources['alternative']->id)
            ->assertJsonPath('data.0.status', 'archived');

        $this->withToken($secret)
            ->getJson('/api/v1/catalog/recipes/' . $otherRecipe->id)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'resource_not_found');

        $this->withToken($secret)
            ->getJson('/api/v1/catalog?resource_type=users')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonPath('error.details.fields.resource_type.0', 'The selected resource_type is invalid.');
    }

    /** @return array{User, Family, Family, string} */
    private function credentialContext(): array
    {
        $issuer = User::factory()->create();
        $family = Family::factory()->create();
        $currentFamily = Family::factory()->create();
        FamilyMembership::factory()->create(['family_id' => $family->id, 'user_id' => $issuer->id]);
        FamilyMembership::factory()->create(['family_id' => $currentFamily->id, 'user_id' => $issuer->id]);
        $issuer->forceFill(['current_family_id' => $currentFamily->id])->save();
        $issued = app(IssueAgentCredential::class)->handle(
            new AuthorizedFamilyContext($issuer, $family),
            'Catalog agent',
        );

        return [$issuer, $family, $currentFamily, $issued->plainTextSecret];
    }

    /**
     * @return array{store: Store, section: StoreSection, ingredient: Ingredient, alternative: Ingredient, tag: RecipeTag, recipe: Recipe, calendar: CalendarEntry}
     */
    private function createCompleteCatalog(Family $family): array
    {
        $store = Store::factory()->for($family)->create(['name' => 'Trh']);
        $section = StoreSection::factory()->for($family)->create(['name' => 'Chlazené']);
        $store->storeSections()->attach($section, ['position' => 0]);
        $ingredient = Ingredient::factory()->for($family)->create([
            'name' => 'Mléko',
            'description' => 'Plnotučné',
            'weight_grams' => '1000',
            'volume_millilitres' => '750.25',
            'piece_count' => '2.5',
            'store_id' => $store->id,
            'store_section_id' => $section->id,
        ]);
        $alternative = Ingredient::factory()->for($family)->create([
            'name' => 'Ovesný nápoj',
            'archived_at' => now(),
        ]);
        IngredientNutritionProfile::factory()->for($ingredient)->create([
            'basis_kind' => 'millilitres',
            'basis_quantity' => '100',
            'energy_kcal' => '42.125',
            'fat_grams' => '1.5',
            'protein_grams' => '3.25',
            'carbohydrate_grams' => '4.75',
        ]);
        DB::table('ingredient_alternatives')->insert([
            'family_id' => $family->id,
            'lower_ingredient_id' => min($ingredient->id, $alternative->id),
            'higher_ingredient_id' => max($ingredient->id, $alternative->id),
        ]);
        $tag = RecipeTag::factory()->for($family)->create(['name' => 'Rychlé']);
        $recipe = Recipe::factory()->for($family)->create([
            'name' => 'Kaše',
            'base_servings' => '4.5',
            'source_url' => 'https://example.test/recept',
            'preparation_minutes' => 15,
            'cooking_minutes' => 30,
            'notes' => 'Podávejte teplé.',
            'nutrition_energy_kcal' => '510.5',
            'nutrition_fat_grams' => '12.25',
            'nutrition_protein_grams' => '18',
            'nutrition_carbohydrate_grams' => '80.125',
        ]);
        RecipeIngredient::factory()->for($recipe)->for($ingredient)->create([
            'family_id' => $family->id,
            'position' => 1,
            'quantity' => '125.75',
            'quantity_kind' => 'millilitres',
        ]);
        RecipeStep::factory()->for($recipe)->create([
            'family_id' => $family->id,
            'position' => 1,
            'instruction' => 'Promíchejte.',
        ]);
        $recipe->tags()->attach($tag, ['family_id' => $family->id]);
        $calendar = CalendarEntry::factory()->for($family)->for($recipe)->create([
            'date' => '2026-08-15',
            'meal_label_key' => 'lunch',
            'serving_count' => '2.25',
        ]);

        return compact('store', 'section', 'ingredient', 'alternative', 'tag', 'recipe', 'calendar');
    }
}
