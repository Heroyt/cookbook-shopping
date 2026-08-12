<?php

declare(strict_types=1);

namespace Tests\Feature\MealPlanning;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\IngredientNutritionProfile;
use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\RecipeIngredient;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\MealPlanning\Models\CalendarEntry;
use App\MealPlanning\Queries\BuildCurrentFamilyCalendarGenerationRequest;
use App\MealPlanning\Queries\BuildCurrentFamilyGenerationRequest;
use App\MealPlanning\Values\ServingCount;
use App\MealPlanning\Values\SimplePlan;
use App\Models\User;
use App\ShoppingGeneration\ShoppingListGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class CalendarTest extends TestCase
{
    public function test_calendar_entry_factory_keeps_the_recipe_in_its_family_and_casts_its_date(): void
    {
        $entry = CalendarEntry::factory()->create(['date' => '2026-08-12']);

        $this->assertSame($entry->family_id, $entry->recipe()->firstOrFail()->family_id);
        $this->assertInstanceOf(CarbonImmutable::class, $entry->date);
        $this->assertSame('2026-08-12', $entry->date->toDateString());
    }

    public function test_guests_cannot_manage_calendar_entries(): void
    {
        $this->get(route('calendar.index'))->assertRedirect(route('login'));
        $this->post(route('calendar.entries.store'))->assertRedirect(route('login'));
        $this->put(route('calendar.entries.update', 1))->assertRedirect(route('login'));
        $this->delete(route('calendar.entries.destroy', 1))->assertRedirect(route('login'));
    }

    public function test_duplicate_create_accumulates_exact_servings_and_persists_an_internal_unlabeled_key(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $recipe = Recipe::factory()->for($family)->create(['name' => 'Lívance']);
        $payload = [
            'recipe_id' => $recipe->id,
            'date' => '2026-08-12',
            'meal_label' => null,
            'serving_count' => '1.25',
        ];

        $this->actingAs($user)->post(route('calendar.entries.store'), $payload)
            ->assertSessionHasNoErrors();
        $this->post(route('calendar.entries.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Záznam byl sloučen do položky bez označení pro recept Lívance. Celkem 2,5 porce.',
            ])
            ->assertRedirect(route('calendar.index', ['week' => '2026-08-10']));

        $this->assertDatabaseCount('calendar_entries', 1);
        $this->assertDatabaseHas('calendar_entries', [
            'family_id' => $family->id,
            'recipe_id' => $recipe->id,
            'date' => '2026-08-12',
            'meal_label_key' => 'unlabeled',
            'serving_count' => '2.500000',
        ]);
        $route = Route::getRoutes()->getByName('calendar.entries.store');
        $this->assertNotNull($route);
        $this->assertSame(10, $route->locksFor());
        $this->assertSame(10, $route->waitsFor());
    }

    public function test_create_can_repeat_atomically_across_consecutive_days(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $recipe = Recipe::factory()->for($family)->create(['name' => 'Polévka']);
        CalendarEntry::factory()->for($family)->for($recipe)->create([
            'date' => '2026-08-13',
            'meal_label_key' => 'dinner',
            'serving_count' => '1',
        ]);

        $this->actingAs($user)->post(route('calendar.entries.store'), [
            'recipe_id' => $recipe->id,
            'date' => '2026-08-12',
            'meal_label' => 'večeře',
            'serving_count' => '2',
            'repeat_days' => 3,
        ])->assertSessionHasNoErrors()->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Přidáno dnů: 2, sloučeno s existujícími: 1.',
        ]);

        $this->assertDatabaseCount('calendar_entries', 3);
        $this->assertDatabaseHas('calendar_entries', ['date' => '2026-08-13', 'serving_count' => '3.000000']);

        $this->post(route('calendar.entries.store'), [
            'recipe_id' => $recipe->id,
            'date' => '2026-08-12',
            'serving_count' => '1',
            'repeat_days' => 32,
        ])->assertSessionHasErrors('repeat_days');
        $this->assertDatabaseCount('calendar_entries', 3);
    }

    public function test_collision_edit_adds_the_submitted_serving_count_to_the_target_and_deletes_the_source(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $recipe = Recipe::factory()->for($family)->create(['name' => 'Polévka']);
        $source = CalendarEntry::factory()->for($family)->for($recipe)->create([
            'date' => '2026-08-11',
            'meal_label_key' => 'lunch',
            'serving_count' => '9',
        ]);
        $target = CalendarEntry::factory()->for($family)->for($recipe)->create([
            'date' => '2026-08-12',
            'meal_label_key' => 'dinner',
            'serving_count' => '2.25',
        ]);

        $this->actingAs($user)->put(route('calendar.entries.update', $source), [
            'recipe_id' => $recipe->id,
            'date' => '2026-08-12',
            'meal_label' => 'večeře',
            'serving_count' => '1.5',
        ])->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Záznam byl sloučen do položky večeře pro recept Polévka. Celkem 3,75 porce.',
            ]);

        $this->assertModelMissing($source);
        $this->assertDatabaseHas('calendar_entries', [
            'id' => $target->id,
            'serving_count' => '3.750000',
        ]);
    }

    public function test_an_archived_recipe_entry_allows_only_serving_count_updates_and_deletion(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $recipe = Recipe::factory()->for($family)->create(['name' => 'Koláč', 'archived_at' => now()]);
        $entry = CalendarEntry::factory()->for($family)->for($recipe)->create([
            'date' => '2026-08-12',
            'meal_label_key' => 'afternoon_snack',
            'serving_count' => '1',
        ]);

        $this->actingAs($user)->put(route('calendar.entries.update', $entry), [
            'recipe_id' => $recipe->id,
            'date' => '2026-08-12',
            'meal_label' => 'odpolední svačina',
            'serving_count' => '2.5',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('calendar_entries', ['id' => $entry->id, 'serving_count' => '2.500000']);

        $this->put(route('calendar.entries.update', $entry), [
            'recipe_id' => $recipe->id,
            'date' => '2026-08-13',
            'meal_label' => 'odpolední svačina',
            'serving_count' => '2.5',
        ])->assertSessionHasErrors([
            'entry' => 'Před změnou data, označení nebo receptu obnovte recept z archivu.',
        ]);
        $this->assertDatabaseHas('calendar_entries', ['id' => $entry->id, 'date' => '2026-08-12']);

        $this->delete(route('calendar.entries.destroy', $entry))
            ->assertSessionHasNoErrors();
        $this->assertModelMissing($entry);
    }

    public function test_calendar_writes_are_current_family_scoped_for_every_member(): void
    {
        $firstMember = User::factory()->create();
        $secondMember = User::factory()->create();
        $family = $this->createFamilyWithMembers($firstMember, $secondMember);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($firstMember, $family);
        $this->selectCurrentFamily($secondMember, $family);
        $recipe = Recipe::factory()->for($family)->create();
        $foreignRecipe = Recipe::factory()->for($otherFamily)->create();

        foreach ([$firstMember, $secondMember] as $index => $member) {
            $this->actingAs($member)->post(route('calendar.entries.store'), [
                'recipe_id' => $recipe->id,
                'date' => '2026-08-' . (12 + $index),
                'meal_label' => 'snídaně',
                'serving_count' => '1',
            ])->assertSessionHasNoErrors();
        }

        $this->actingAs($firstMember)->post(route('calendar.entries.store'), [
            'recipe_id' => $foreignRecipe->id,
            'date' => '2026-08-14',
            'meal_label' => 'snídaně',
            'serving_count' => '1',
        ])->assertSessionHasErrors([
            'recipe_id' => 'Vybraný recept není dostupný v aktuální rodině.',
        ]);
        $this->assertDatabaseCount('calendar_entries', 2);
        $this->assertDatabaseMissing('calendar_entries', ['family_id' => $otherFamily->id]);
    }

    public function test_calendar_entry_validation_is_canonical_and_czech(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $recipe = Recipe::factory()->for($family)->create();

        $this->actingAs($user)->post(route('calendar.entries.store'), [
            'recipe_id' => $recipe->id,
            'date' => '12.08.2026',
            'meal_label' => 'brunch',
            'serving_count' => '01.5',
        ])->assertSessionHasErrors([
            'date' => 'Zadejte datum ve formátu RRRR-MM-DD.',
            'meal_label' => 'Vyberte platné označení jídla.',
            'serving_count' => 'Počet porcí musí být kladné desetinné číslo s nejvýše šesti desetinnými místy.',
        ]);
    }

    public function test_collision_overflow_rolls_back_both_calendar_entries(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $recipe = Recipe::factory()->for($family)->create();
        $source = CalendarEntry::factory()->for($family)->for($recipe)->create([
            'date' => '2026-08-11', 'meal_label_key' => 'lunch', 'serving_count' => '9',
        ]);
        $target = CalendarEntry::factory()->for($family)->for($recipe)->create([
            'date' => '2026-08-12', 'meal_label_key' => 'dinner', 'serving_count' => '99999999999999.999999',
        ]);

        $this->actingAs($user)->put(route('calendar.entries.update', $source), [
            'recipe_id' => $recipe->id,
            'date' => '2026-08-12',
            'meal_label' => 'večeře',
            'serving_count' => '0.000001',
        ])->assertSessionHasErrors([
            'serving_count' => 'Výsledný počet porcí je mimo podporovaný rozsah.',
        ]);

        $this->assertDatabaseHas('calendar_entries', [
            'id' => $source->id, 'date' => '2026-08-11', 'serving_count' => '9.000000',
        ]);
        $this->assertDatabaseHas('calendar_entries', [
            'id' => $target->id, 'date' => '2026-08-12', 'serving_count' => '99999999999999.999999',
        ]);
    }

    public function test_database_rejects_unknown_meal_keys_and_cross_family_recipe_references(): void
    {
        $family = $this->createFamilyWithMembers(User::factory()->create());
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $recipe = Recipe::factory()->for($family)->create();
        $foreignRecipe = Recipe::factory()->for($otherFamily)->create();

        try {
            CalendarEntry::query()->create([
                'family_id' => $family->id,
                'recipe_id' => $recipe->id,
                'date' => '2026-08-12',
                'meal_label_key' => 'brunch',
                'serving_count' => '1',
            ]);
            $this->fail('An unknown Meal Label key was accepted.');
        } catch (QueryException) {
            $this->assertDatabaseCount('calendar_entries', 0);
        }

        $this->expectException(QueryException::class);
        CalendarEntry::query()->create([
            'family_id' => $family->id,
            'recipe_id' => $foreignRecipe->id,
            'date' => '2026-08-12',
            'meal_label_key' => 'lunch',
            'serving_count' => '1',
        ]);
    }

    public function test_generation_requires_unique_canonical_dates_with_current_family_entries(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);

        $this->actingAs($user)->post(route('calendar.generate'), [
            'dates' => ['2026-08-12', '2026-08-12', '12.08.2026'],
        ])->assertSessionHasErrors([
            'dates.1' => 'Každé datum kalendáře vyberte pouze jednou.',
            'dates.2' => 'Zadejte datum ve formátu RRRR-MM-DD.',
        ]);

        $this->post(route('calendar.generate'), ['dates' => ['2026-08-12']])
            ->assertSessionHasErrors([
                'dates' => 'Vyberte alespoň jedno datum kalendáře s receptem.',
            ]);
    }

    public function test_weekly_projection_derives_ordered_days_and_exact_incomplete_nutrition_for_the_current_family(): void
    {
        $user = User::factory()->create();
        $secondMember = User::factory()->create();
        $family = $this->createFamilyWithMembers($user, $secondMember);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($user, $family);
        $this->selectCurrentFamily($secondMember, $family);
        $completeRecipe = Recipe::factory()->for($family)->create([
            'name' => 'Kaše',
            'nutrition_energy_kcal' => '100',
            'nutrition_fat_grams' => '4',
            'nutrition_protein_grams' => '5',
            'nutrition_carbohydrate_grams' => '6',
        ]);
        $knownIngredient = Ingredient::factory()->for($family)->create(['name' => 'Mouka', 'weight_grams' => '500']);
        IngredientNutritionProfile::factory()->for($knownIngredient)->create([
            'basis_kind' => 'grams', 'basis_quantity' => '100', 'energy_kcal' => '200',
            'fat_grams' => '2', 'protein_grams' => '10', 'carbohydrate_grams' => '40',
        ]);
        $missingIngredient = Ingredient::factory()->for($family)->create(['name' => 'Tajemství', 'weight_grams' => '100']);
        $incompleteRecipe = Recipe::factory()->for($family)->create([
            'name' => 'Archivní koláč', 'base_servings' => '2', 'archived_at' => now(),
        ]);
        RecipeIngredient::factory()->for($incompleteRecipe)->for($knownIngredient)->create([
            'family_id' => $family->id, 'position' => 1, 'quantity' => '100', 'quantity_kind' => 'grams',
        ]);
        RecipeIngredient::factory()->for($incompleteRecipe)->for($missingIngredient)->create([
            'family_id' => $family->id, 'position' => 2, 'quantity' => '1', 'quantity_kind' => 'grams',
        ]);
        CalendarEntry::factory()->for($family)->for($completeRecipe)->create([
            'date' => '2026-08-12', 'meal_label_key' => 'lunch', 'serving_count' => '1.5',
        ]);
        CalendarEntry::factory()->for($family)->for($incompleteRecipe)->create([
            'date' => '2026-08-12', 'meal_label_key' => 'unlabeled', 'serving_count' => '2',
        ]);
        $foreignRecipe = Recipe::factory()->for($otherFamily)->create();
        CalendarEntry::factory()->for($otherFamily)->for($foreignRecipe)->create([
            'date' => '2026-08-12', 'meal_label_key' => 'lunch',
        ]);

        $this->actingAs($user)->get(route('calendar.index', ['week' => '2026-08-12']))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('calendar/Index')
                ->where('week.startsOn', '2026-08-10')
                ->where('week.endsOn', '2026-08-16')
                ->where('week.previousStartsOn', '2026-08-03')
                ->where('week.nextStartsOn', '2026-08-17')
                ->has('recipes', 1)
                ->where('recipes.0.id', $completeRecipe->id)
                ->has('mealLabels', 5)
                ->where('mealLabels.0.value', 'snídaně')
                ->where('mealLabels.4.value', 'večeře')
                ->has('days', 7)
                ->where('days.2.date', '2026-08-12')
                ->where('days.2.groups.0.label', 'snídaně')
                ->where('days.2.groups.2.label', 'oběd')
                ->where('days.2.groups.2.entries.0.recipeName', 'Kaše')
                ->where('days.2.groups.2.entries.0.nutrition.totals.energyKcal', '150.000000')
                ->where('days.2.groups.5.label', 'bez označení')
                ->where('days.2.groups.5.entries.0.recipeName', 'Archivní koláč')
                ->where('days.2.groups.5.entries.0.recipeArchived', true)
                ->where('days.2.groups.5.entries.0.nutrition.status', 'incomplete')
                ->where('days.2.groups.5.entries.0.nutrition.totals.energyKcal', '200.000000')
                ->where('days.2.groups.5.entries.0.nutrition.missingIngredientNames.0', 'Tajemství')
                ->where('days.2.nutrition.status', 'incomplete')
                ->where('days.2.nutrition.totals.energyKcal', '350.000000')
                ->where('days.2.nutrition.totals.fatGrams', '8.000000')
                ->where('days.2.nutrition.totals.proteinGrams', '17.500000')
                ->where('days.2.nutrition.totals.carbohydrateGrams', '49.000000')
                ->where('days.2.nutrition.missingIngredientNames.0', 'Tajemství')
                ->has('days.0.groups', 6)
                ->has('days.0.groups.0.entries', 0));

        $this->assertDatabaseCount('calendar_entries', 3);
        $this->assertFalse(Schema::hasTable('calendar_days'));
        $this->actingAs($secondMember)->get(route('calendar.index', ['week' => '2026-08-12']))
            ->assertInertia(fn (Assert $page): Assert => $page
                ->where('days.2.groups.2.entries.0.recipeName', 'Kaše')
                ->where('days.2.groups.5.entries.0.recipeName', 'Archivní koláč'));
    }

    public function test_non_contiguous_calendar_dates_produce_the_same_generator_input_and_refresh_safe_output_as_an_equivalent_simple_plan(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($user, $family);
        $ingredient = Ingredient::factory()->for($family)->create([
            'name' => 'Mouka', 'weight_grams' => '250',
        ]);
        $recipe = Recipe::factory()->for($family)->create([
            'name' => 'Chléb', 'base_servings' => '1',
        ]);
        RecipeIngredient::factory()->for($recipe)->for($ingredient)->create([
            'family_id' => $family->id, 'quantity' => '100', 'quantity_kind' => 'grams',
        ]);
        CalendarEntry::factory()->for($family)->for($recipe)->create([
            'date' => '2026-08-10', 'meal_label_key' => 'breakfast', 'serving_count' => '1.5',
        ]);
        CalendarEntry::factory()->for($family)->for($recipe)->create([
            'date' => '2026-08-12', 'meal_label_key' => 'unlabeled', 'serving_count' => '2.25',
        ]);
        CalendarEntry::factory()->for($family)->for($recipe)->create([
            'date' => '2026-08-11', 'meal_label_key' => 'dinner', 'serving_count' => '50',
        ]);
        $foreignRecipe = Recipe::factory()->for($otherFamily)->create();
        CalendarEntry::factory()->for($otherFamily)->for($foreignRecipe)->create([
            'date' => '2026-08-10', 'meal_label_key' => 'breakfast', 'serving_count' => '50',
        ]);
        $dates = ['2026-08-10', '2026-08-12'];
        $equivalentPlan = SimplePlan::empty()->add($recipe->id, ServingCount::from('3.75'));

        $calendarRequest = app(BuildCurrentFamilyCalendarGenerationRequest::class)->handle($family, $dates);
        $simplePlanRequest = app(BuildCurrentFamilyGenerationRequest::class)->handle($family, $equivalentPlan);

        $this->assertEquals($simplePlanRequest, $calendarRequest);
        $this->assertEquals(
            app(ShoppingListGenerator::class)->generate($simplePlanRequest),
            app(ShoppingListGenerator::class)->generate($calendarRequest),
        );

        $this->actingAs($user)->post(route('calendar.generate'), ['dates' => $dates])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('calendar.generated'))
            ->assertInertiaFlash('toast', [
                'type' => 'success', 'message' => 'Nákupní seznam z kalendáře byl vytvořen.',
            ]);
        foreach (range(1, 2) as $refresh) {
            $this->get(route('calendar.generated'))
                ->assertOk()
                ->assertInertia(fn (Assert $page): Assert => $page
                    ->component('calendar/Generated')
                    ->where('selectedDates', $dates)
                    ->where('shoppingList.unplacedLines.0.ingredientName', 'Mouka')
                    ->where('shoppingList.unplacedLines.0.quantities.0.required.label', '375 g')
                    ->where('shoppingList.unplacedLines.0.purchasePackages', '2'));
        }

        $this->assertSame($dates, session("meal_planning.calendar.dates.{$family->id}"));
        $this->assertFalse(Schema::hasTable('shopping_lists'));
    }

    public function test_calendar_generation_applies_and_reverts_current_family_alternatives(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $original = Ingredient::factory()->for($family)->create(['name' => 'Mouka', 'weight_grams' => '100']);
        $alternative = Ingredient::factory()->for($family)->create(['name' => 'Špaldová mouka', 'weight_grams' => '100']);
        DB::table('ingredient_alternatives')->insert([
            'family_id' => $family->id,
            'lower_ingredient_id' => min($original->id, $alternative->id),
            'higher_ingredient_id' => max($original->id, $alternative->id),
        ]);
        $recipe = Recipe::factory()->for($family)->create(['base_servings' => '1']);
        RecipeIngredient::factory()->for($recipe)->for($original)->create([
            'family_id' => $family->id, 'quantity' => '50', 'quantity_kind' => 'grams',
        ]);
        CalendarEntry::factory()->for($family)->for($recipe)->create([
            'date' => '2026-08-12', 'meal_label_key' => 'lunch', 'serving_count' => '1',
        ]);

        $this->actingAs($user)->post(route('calendar.generate'), ['dates' => ['2026-08-12']])
            ->assertSessionHasNoErrors();
        $this->post(route('calendar.alternatives.store'), [
            'original_ingredient_id' => $original->id,
            'alternative_ingredient_id' => $alternative->id,
        ])->assertSessionHasNoErrors()
            ->assertRedirect(route('calendar.generated'));
        $this->get(route('calendar.generated'))->assertInertia(fn (Assert $page): Assert => $page
            ->where('shoppingList.unplacedLines.0.ingredientName', 'Špaldová mouka'));

        $this->post(route('calendar.generate'), ['dates' => ['2026-08-12']])
            ->assertSessionHasNoErrors();
        $this->get(route('calendar.generated'))->assertInertia(fn (Assert $page): Assert => $page
            ->where('shoppingList.unplacedLines.0.ingredientName', 'Špaldová mouka'));

        CalendarEntry::factory()->for($family)->for($recipe)->create([
            'date' => '2026-08-13', 'meal_label_key' => 'dinner', 'serving_count' => '1',
        ]);
        $this->post(route('calendar.generate'), ['dates' => ['2026-08-13']])
            ->assertSessionHasNoErrors();
        $this->get(route('calendar.generated'))->assertInertia(fn (Assert $page): Assert => $page
            ->where('selectedDates', ['2026-08-13'])
            ->where('shoppingList.unplacedLines.0.ingredientName', 'Mouka'));
        $this->assertNull(session("meal_planning.calendar.alternatives.{$family->id}"));

        $this->delete(route('calendar.alternatives.destroy', $original))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('calendar.generated'));
        $this->get(route('calendar.generated'))->assertInertia(fn (Assert $page): Assert => $page
            ->where('shoppingList.unplacedLines.0.ingredientName', 'Mouka'));
    }

    public function test_calendar_generation_uses_calendar_specific_czech_correction_copy(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $validIngredient = Ingredient::factory()->for($family)->create(['name' => 'Původní surovina']);
        $validRecipe = Recipe::factory()->for($family)->create(['name' => 'Původní recept']);
        RecipeIngredient::factory()->for($validRecipe)->for($validIngredient)->create([
            'family_id' => $family->id,
            'quantity' => '1',
            'quantity_kind' => 'grams',
        ]);
        CalendarEntry::factory()->for($family)->for($validRecipe)->create([
            'date' => '2026-08-11',
        ]);
        $recipe = Recipe::factory()->for($family)->create(['name' => 'Prázdný recept']);
        CalendarEntry::factory()->for($family)->for($recipe)->create([
            'date' => '2026-08-12',
        ]);

        $this->actingAs($user)
            ->post(route('calendar.generate'), ['dates' => ['2026-08-11']])
            ->assertSessionHasNoErrors();
        $this->get(route('calendar.generated'))
            ->assertInertia(fn (Assert $page): Assert => $page
                ->where('selectedDates', ['2026-08-11'])
                ->where('shoppingList.unplacedLines.0.ingredientName', 'Původní surovina'));

        $this
            ->post(route('calendar.generate'), ['dates' => ['2026-08-12']])
            ->assertRedirect()
            ->assertSessionHasErrors([
                'dates' => 'Výběr z kalendáře obsahuje recept bez surovin. Upravte recept a zkuste to znovu.',
            ]);

        $this->get(route('calendar.generated'))
            ->assertRedirect(route('calendar.index'));

        $this->get(route('calendar.index', ['week' => '2026-08-12']))
            ->assertInertia(fn (Assert $page): Assert => $page
                ->where('selectedDates', ['2026-08-12']));

        $ingredient = Ingredient::factory()->for($family)->create(['name' => 'Opravená surovina']);
        RecipeIngredient::factory()->for($recipe)->for($ingredient)->create([
            'family_id' => $family->id,
            'quantity' => '1',
            'quantity_kind' => 'grams',
        ]);
        $this->post(route('calendar.generate'), ['dates' => ['2026-08-12']])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('calendar.generated'));
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
