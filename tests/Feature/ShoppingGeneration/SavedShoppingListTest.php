<?php

declare(strict_types=1);

namespace Tests\Feature\ShoppingGeneration;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\RecipeIngredient;
use App\Cookbook\Models\Store;
use App\Cookbook\Models\StoreSection;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\MealPlanning\Models\CalendarEntry;
use App\Models\User;
use App\ShoppingGeneration\Models\SavedShoppingList;
use App\ShoppingGeneration\Queries\CurrentFamilySavedShoppingLists;
use App\ShoppingGeneration\Snapshots\SavedShoppingListPayload;
use App\ShoppingGeneration\Snapshots\SavedShoppingListV1;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use LogicException;
use Tests\TestCase;

/**
 * @phpstan-import-type ShoppingListV1 from SavedShoppingListV1
 *
 * @phpstan-type PresentationFixture array{shoppingList: ShoppingListV1, problems: list<never>}
 */
final class SavedShoppingListTest extends TestCase
{
    public function test_guests_cannot_use_generation_history(): void
    {
        $snapshot = SavedShoppingList::factory()->create();

        $this->get(route('shopping-list-history.index'))->assertRedirect(route('login'));
        $this->get(route('shopping-list-history.show', $snapshot))->assertRedirect(route('login'));
        $this->post(route('shopping-list-history.simple-plan.store'))->assertRedirect(route('login'));
        $this->post(route('shopping-list-history.calendar.store'))->assertRedirect(route('login'));
        $this->delete(route('shopping-list-history.destroy', $snapshot))->assertRedirect(route('login'));
    }

    public function test_every_simple_plan_save_creates_a_distinct_lossless_snapshot(): void
    {
        CarbonImmutable::setTestNow('2026-08-11 12:34:56.123456');
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $recipe = Recipe::factory()->for($family)->create(['name' => 'Lívance']);
        $presentation = $this->presentation($recipe->id, 'Mouka', '133333/100', '1,33 kg');
        $presentation['shoppingList']['futureTransientField'] = 'must not enter v1';
        $presentation['shoppingList']['unplacedLines'][0]['futureTransientField'] = 'must not enter v1';

        $this->actingAs($user)->withSession([
            "meal_planning.simple_plan.{$family->id}" => [(string) $recipe->id => '2.500000'],
            "meal_planning.generated.{$family->id}" => $presentation,
            "meal_planning.alternatives.{$family->id}" => [15 => 16],
        ]);

        foreach (range(1, 2) as $save) {
            $this->post(route('shopping-list-history.simple-plan.store'))
                ->assertSessionHasNoErrors()
                ->assertInertiaFlash('toast', [
                    'type' => 'success',
                    'message' => 'Nákupní seznam byl uložen do historie.',
                ])
                ->assertRedirect();
        }

        $this->assertDatabaseCount('saved_shopping_lists', 2);
        $snapshots = SavedShoppingList::query()->orderBy('id')->get();
        $this->assertNotSame($snapshots[0]->id, $snapshots[1]->id);
        foreach ($snapshots as $snapshot) {
            $this->assertSame($family->id, $snapshot->family_id);
            $this->assertSame('simple_plan', $snapshot->source_kind->value);
            $this->assertSame(1, $snapshot->payload_schema_version);
            $this->assertSame('123456', $snapshot->generated_at->format('u'));
            $this->assertSame('cs', $snapshot->payload['locale']);
            $this->assertSame('133333/100', $snapshot->payload['shoppingList']['unplacedLines'][0]['quantities'][0]['required']['exact']);
            $this->assertSame('1,33 kg', $snapshot->payload['shoppingList']['unplacedLines'][0]['quantities'][0]['required']['label']);
            $this->assertSame('1000', $snapshot->payload['shoppingList']['unplacedLines'][0]['package']['grams']);
            $this->assertSame('133333/100000', $snapshot->payload['shoppingList']['unplacedLines'][0]['contributions'][0]['packageFraction']);
            $this->assertArrayNotHasKey('futureTransientField', $snapshot->payload['shoppingList']);
            $this->assertArrayNotHasKey('futureTransientField', $snapshot->payload['shoppingList']['unplacedLines'][0]);
            $this->assertSame([
                'kind' => 'simple_plan',
                'recipes' => [[
                    'recipeId' => $recipe->id,
                    'recipeName' => 'Lívance',
                    'servingCount' => '2.5',
                    'servingCountLabel' => '2,5 porce',
                ]],
            ], $snapshot->payload['source']);
            $this->assertSame([[
                'originalIngredientId' => 15,
                'originalIngredientName' => 'Původní mouka',
                'alternativeIngredientId' => 16,
                'alternativeIngredientName' => 'Mouka',
            ]], $snapshot->payload['appliedAlternatives']);
        }

        $this->get(route('shopping-list-history.index'))
            ->assertInertia(fn (Assert $page): Assert => $page
                ->where('snapshots.0.generatedAt', '11. 8. 2026 12:34:56,123456')
                ->where('snapshots.1.generatedAt', '11. 8. 2026 12:34:56,123456'));
    }

    public function test_calendar_save_freezes_live_output_and_date_provenance(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $store = Store::factory()->for($family)->create(['name' => 'Albert']);
        $section = StoreSection::factory()->for($family)->create(['name' => 'Pečení']);
        $store->storeSections()->attach($section->id, ['position' => 0]);
        $ingredient = Ingredient::factory()->for($family)->create([
            'name' => 'Mouka',
            'weight_grams' => '150',
            'store_id' => $store->id,
            'store_section_id' => $section->id,
        ]);
        $recipe = Recipe::factory()->for($family)->create(['name' => 'Lívance', 'base_servings' => '4']);
        RecipeIngredient::factory()->for($recipe)->for($ingredient)->create([
            'family_id' => $family->id,
            'position' => 1,
            'quantity' => '280',
            'quantity_kind' => 'grams',
        ]);
        CalendarEntry::factory()->for($family)->for($recipe)->create([
            'date' => '2026-08-12',
            'meal_label_key' => 'breakfast',
            'serving_count' => '2.5',
        ]);

        $this->actingAs($user)->post(route('calendar.generate'), ['dates' => ['2026-08-12']])
            ->assertSessionHasNoErrors();
        $this->post(route('shopping-list-history.calendar.store'))
            ->assertSessionHasNoErrors();

        $snapshot = SavedShoppingList::query()->sole();
        $this->assertSame('calendar', $snapshot->source_kind->value);
        $this->assertSame([
            'kind' => 'calendar',
            'dates' => ['2026-08-12'],
            'dateLabels' => ['12. 8. 2026'],
        ], $snapshot->payload['source']);
        $this->assertSame('175', $snapshot->payload['shoppingList']['storeGroups'][0]['sections'][0]['lines'][0]['quantities'][0]['required']['exact']);

        $recipe->forceFill(['name' => 'Přejmenované lívance', 'archived_at' => now()])->save();
        $ingredient->forceFill(['name' => 'Přejmenovaná mouka', 'archived_at' => now()])->save();
        $this->delete(route('store-sections.destroy', $section))->assertSessionHasNoErrors();
        $this->delete(route('stores.destroy', $store))->assertSessionHasNoErrors();

        $this->get(route('shopping-list-history.show', $snapshot))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('shopping-list-history/Show')
                ->where('snapshot.sourceKind', 'calendar')
                ->where('snapshot.source.dates', ['2026-08-12'])
                ->where('snapshot.shoppingList.storeGroups.0.storeName', 'Albert')
                ->where('snapshot.shoppingList.storeGroups.0.sections.0.sectionName', 'Pečení')
                ->where('snapshot.shoppingList.storeGroups.0.sections.0.lines.0.ingredientName', 'Mouka')
                ->where('snapshot.shoppingList.storeGroups.0.sections.0.lines.0.contributions.0.recipeName', 'Lívance'));
    }

    public function test_simple_plan_source_freezes_czech_serving_count_inflection(): void
    {
        $recipe = Recipe::factory()->create(['name' => 'Lívance']);

        $payload = $this->app->make(SavedShoppingListPayload::class)->forSimplePlan(
            $this->presentation($recipe->id, 'Mouka', '175', '175 g'),
            [$recipe->id => '5'],
        );

        $this->assertSame('5 porcí', $payload['source']['recipes'][0]['servingCountLabel']);
    }

    public function test_history_is_current_family_scoped_and_any_member_may_delete_a_snapshot(): void
    {
        $firstMember = User::factory()->create();
        $secondMember = User::factory()->create();
        $family = $this->createFamilyWithMembers($firstMember, $secondMember);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($firstMember, $family);
        $this->selectCurrentFamily($secondMember, $family);
        $currentSnapshot = SavedShoppingList::factory()->for($family)->create();
        $foreignSnapshot = SavedShoppingList::factory()->for($otherFamily)->create();

        foreach ([$firstMember, $secondMember] as $member) {
            $this->actingAs($member)->get(route('shopping-list-history.index'))
                ->assertOk()
                ->assertInertia(fn (Assert $page): Assert => $page
                    ->component('shopping-list-history/Index')
                    ->has('snapshots', 1)
                    ->where('snapshots.0.id', $currentSnapshot->id));
            $this->get(route('shopping-list-history.show', $foreignSnapshot))->assertNotFound();
            $this->delete(route('shopping-list-history.destroy', $foreignSnapshot))->assertNotFound();
        }

        $this->actingAs($secondMember)->delete(route('shopping-list-history.destroy', $currentSnapshot))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Uložený nákupní seznam byl smazán.',
            ])
            ->assertRedirect(route('shopping-list-history.index'));
        $this->assertModelMissing($currentSnapshot);
        $this->assertModelExists($foreignSnapshot);
    }

    public function test_history_index_does_not_load_large_snapshot_payloads(): void
    {
        $family = Family::factory()->create();
        SavedShoppingList::factory()->for($family)->create();

        $page = $this->app->make(CurrentFamilySavedShoppingLists::class)->page($family);
        $summary = $page->items()[0];

        $this->assertArrayNotHasKey('payload', $summary->getAttributes());
        $this->assertArrayHasKey('payload_schema_version', $summary->getAttributes());
        $this->assertSame(24, $page->perPage());
    }

    public function test_only_a_complete_transient_result_can_be_saved_and_family_deletion_cascades_history(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);

        $this->actingAs($user)->post(route('shopping-list-history.simple-plan.store'))
            ->assertSessionHasErrors(['snapshot' => 'Nejprve vytvořte úplný nákupní seznam.']);

        $this->withSession([
            "meal_planning.generated.{$family->id}" => ['shoppingList' => null, 'problems' => [['message' => 'Oprava']]],
        ])->post(route('shopping-list-history.simple-plan.store'))
            ->assertSessionHasErrors(['snapshot' => 'Nákupní seznam s problémy nelze uložit.']);

        $snapshot = SavedShoppingList::factory()->for($family)->create();
        $family->delete();
        $this->assertModelMissing($snapshot);
    }

    public function test_schema_and_routes_expose_the_immutable_versioned_history_contract(): void
    {
        $this->assertTrue(Schema::hasColumns('saved_shopping_lists', [
            'id',
            'family_id',
            'generated_at',
            'source_kind',
            'payload_schema_version',
            'payload',
        ]));
        $this->assertFalse(Schema::hasColumn('saved_shopping_lists', 'updated_at'));

        foreach ([
            'shopping-list-history.simple-plan.store',
            'shopping-list-history.calendar.store',
            'shopping-list-history.destroy',
        ] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route);
            $this->assertSame(10, $route->locksFor());
            $this->assertSame(10, $route->waitsFor());
        }
    }

    public function test_saved_payloads_reject_updates_and_retain_the_original_version(): void
    {
        $snapshot = SavedShoppingList::factory()->create();
        $originalPayload = $snapshot->payload;

        try {
            $snapshot->forceFill([
                'payload_schema_version' => 2,
                'payload' => ['tampered' => true],
            ])->save();
            $this->fail('An immutable Saved Shopping List update was accepted.');
        } catch (LogicException $exception) {
            $this->assertSame('Saved Shopping Lists are immutable.', $exception->getMessage());
        }

        $snapshot->refresh();
        $this->assertSame(1, $snapshot->payload_schema_version);
        $this->assertSame($originalPayload, $snapshot->payload);
    }

    public function test_unsupported_or_corrupt_payload_versions_render_an_intentional_czech_state(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $unsupported = SavedShoppingList::factory()->for($family)->create([
            'payload_schema_version' => 99,
        ]);
        $corrupt = SavedShoppingList::factory()->for($family)->create([
            'payload' => ['locale' => 'cs', 'source' => [], 'shoppingList' => []],
        ]);

        $this->actingAs($user)->get(route('shopping-list-history.show', $unsupported))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->where('snapshot.status', 'unavailable')
                ->where('snapshot.unavailableMessage', 'Tato verze uloženého nákupního seznamu není podporována.')
                ->missing('snapshot.shoppingList'));
        $this->get(route('shopping-list-history.show', $corrupt))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->where('snapshot.status', 'unavailable')
                ->where('snapshot.unavailableMessage', 'Uložený nákupní seznam je poškozený a nelze jej zobrazit.')
                ->missing('snapshot.shoppingList'));
    }

    /** @return PresentationFixture */
    private function presentation(int $recipeId, string $ingredientName, string $requiredExact, string $requiredLabel): array
    {
        return [
            'shoppingList' => [
                'storeGroups' => [],
                'unplacedLines' => [[
                    'ingredientId' => 16,
                    'ingredientName' => $ingredientName,
                    'package' => ['grams' => '1000', 'millilitres' => null, 'piece' => null],
                    'purchasePackages' => '2',
                    'quantities' => [[
                        'kind' => 'grams',
                        'required' => ['exact' => $requiredExact, 'label' => $requiredLabel, 'value' => '1.33', 'unit' => 'kg', 'approximate' => true],
                        'purchased' => ['exact' => '2000', 'label' => '2 kg', 'value' => '2', 'unit' => 'kg', 'approximate' => false],
                        'surplus' => ['exact' => '66667/100', 'label' => '666,67 g', 'value' => '666.67', 'unit' => 'g', 'approximate' => false],
                    ]],
                    'contributions' => [[
                        'contributionKey' => '1:15:grams:0',
                        'recipeId' => $recipeId,
                        'recipeName' => 'Lívance',
                        'originalIngredientId' => 15,
                        'originalIngredientName' => 'Původní mouka',
                        'quantityKind' => 'grams',
                        'required' => ['exact' => $requiredExact, 'label' => $requiredLabel, 'value' => '1.33', 'unit' => 'kg', 'approximate' => true],
                        'packageFraction' => '133333/100000',
                    ]],
                    'eligibleAlternatives' => [],
                    'alternativeChoices' => [[
                        'originalIngredientId' => 15,
                        'originalIngredientName' => 'Původní mouka',
                        'alternativeIngredientId' => 16,
                        'alternativeIngredientName' => $ingredientName,
                    ]],
                ]],
            ],
            'problems' => [],
        ];
    }

    /** @param list<User> $members */
    private function createFamilyWithMembers(User ...$members): Family
    {
        $family = Family::factory()->create();

        foreach ($members as $member) {
            FamilyMembership::factory()->for($family)->for($member)->create();
        }

        return $family;
    }

    private function selectCurrentFamily(User $user, Family $family): void
    {
        $user->forceFill(['current_family_id' => $family->id])->save();
    }
}
