<?php

declare(strict_types=1);

namespace Tests\Feature\MealPlanning;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\RecipeIngredient;
use App\Cookbook\Models\Store;
use App\Cookbook\Models\StoreSection;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class SimplePlanTest extends TestCase
{
    public function test_guests_cannot_use_the_simple_plan(): void
    {
        $this->get(route('simple-plan.index'))->assertRedirect(route('login'));
        $this->get(route('simple-plan.generated'))->assertRedirect(route('login'));
        $this->post(route('simple-plan.selections.store'))->assertRedirect(route('login'));
        $this->post(route('simple-plan.generate'))->assertRedirect(route('login'));
    }

    public function test_each_member_can_see_only_active_current_family_recipes(): void
    {
        $firstMember = User::factory()->create();
        $secondMember = User::factory()->create();
        $family = $this->createFamilyWithMembers($firstMember, $secondMember);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($firstMember, $family);
        $this->selectCurrentFamily($secondMember, $family);
        Recipe::factory()->for($family)->create(['name' => 'Aktivní recept']);
        Recipe::factory()->for($family)->create(['name' => 'Archivovaný recept', 'archived_at' => now()]);
        Recipe::factory()->for($otherFamily)->create(['name' => 'Cizí recept']);

        foreach ([$firstMember, $secondMember] as $member) {
            $this->actingAs($member)->get(route('simple-plan.index'))
                ->assertOk()
                ->assertInertia(fn (Assert $page): Assert => $page
                    ->component('simple-plan/Index')
                    ->has('recipes', 1)
                    ->where('recipes.0.name', 'Aktivní recept')
                    ->has('selections', 0));
        }
    }

    public function test_duplicate_fractional_add_accumulates_one_exact_selection_with_resulting_total_notice(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $recipe = Recipe::factory()->for($family)->create(['name' => 'Lívance']);

        $this->actingAs($user)->post(route('simple-plan.selections.store'), [
            'recipe_id' => $recipe->id,
            'serving_count' => '1.5',
        ])->assertSessionHasNoErrors();

        $this->post(route('simple-plan.selections.store'), [
            'recipe_id' => $recipe->id,
            'serving_count' => '0.75',
        ])->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Recept Lívance je v rychlém plánu celkem pro 2,25 porce.',
            ])
            ->assertRedirect(route('simple-plan.index'));

        $this->get(route('simple-plan.index'))->assertInertia(fn (Assert $page): Assert => $page
            ->has('selections', 1)
            ->where('selections.0.recipeId', $recipe->id)
            ->where('selections.0.servingCount', '2.25'));
        $this->assertSame(
            [(string) $recipe->id => '2.25'],
            session("meal_planning.simple_plan.{$family->id}"),
        );
        $storeRoute = Route::getRoutes()->getByName('simple-plan.selections.store');
        $this->assertNotNull($storeRoute);
        $this->assertSame(10, $storeRoute->locksFor());
        $this->assertSame(10, $storeRoute->waitsFor());
    }

    public function test_accumulation_overflow_is_a_czech_field_error_and_keeps_the_previous_total(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $recipe = Recipe::factory()->for($family)->create();
        $maximum = '99999999999999.999999';

        $this->actingAs($user)->withSession([
            "meal_planning.simple_plan.{$family->id}" => [(string) $recipe->id => $maximum],
        ])->post(route('simple-plan.selections.store'), [
            'recipe_id' => $recipe->id,
            'serving_count' => '0.000001',
        ])->assertSessionHasErrors([
            'serving_count' => 'Výsledný počet porcí je mimo podporovaný rozsah.',
        ]);

        $this->assertSame(
            [(string) $recipe->id => $maximum],
            session("meal_planning.simple_plan.{$family->id}"),
        );
    }

    public function test_foreign_and_archived_recipes_cannot_be_added_and_family_plans_are_isolated(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $otherFamily = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $currentRecipe = Recipe::factory()->for($family)->create(['name' => 'Rodinný recept']);
        $archivedRecipe = Recipe::factory()->for($family)->create(['archived_at' => now()]);
        $foreignRecipe = Recipe::factory()->for($otherFamily)->create();

        foreach ([$archivedRecipe, $foreignRecipe] as $unavailableRecipe) {
            $this->actingAs($user)->post(route('simple-plan.selections.store'), [
                'recipe_id' => $unavailableRecipe->id,
                'serving_count' => '1',
            ])->assertSessionHasErrors([
                'recipe_id' => 'Vybraný recept není dostupný v aktuální rodině.',
            ]);
        }

        $this->post(route('simple-plan.selections.store'), [
            'recipe_id' => $currentRecipe->id,
            'serving_count' => '1',
        ])->assertSessionHasNoErrors();

        $this->selectCurrentFamily($user, $otherFamily);
        $this->get(route('simple-plan.index'))->assertInertia(fn (Assert $page): Assert => $page->has('selections', 0));

        $this->selectCurrentFamily($user, $family);
        $this->get(route('simple-plan.index'))->assertInertia(fn (Assert $page): Assert => $page
            ->has('selections', 1)
            ->where('selections.0.recipeId', $currentRecipe->id));
    }

    public function test_a_member_can_remove_a_recipe_selection_without_affecting_recipe_persistence(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $recipe = Recipe::factory()->for($family)->create(['name' => 'Polévka']);

        $this->actingAs($user)->withSession([
            "meal_planning.simple_plan.{$family->id}" => [(string) $recipe->id => '1.5'],
        ])->delete(route('simple-plan.selections.destroy', $recipe))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Recept Polévka byl z rychlého plánu odebrán.',
            ])
            ->assertRedirect(route('simple-plan.index'));

        $this->assertSame([], session("meal_planning.simple_plan.{$family->id}"));
        $this->assertDatabaseHas('recipes', ['id' => $recipe->id, 'name' => 'Polévka']);
    }

    public function test_fractional_servings_generate_a_refresh_safe_result_without_durable_plan_persistence(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $ingredient = Ingredient::factory()->for($family)->create([
            'name' => 'Mouka',
            'weight_grams' => '150',
        ]);
        $recipe = Recipe::factory()->for($family)->create([
            'name' => 'Lívance',
            'base_servings' => '4',
        ]);
        RecipeIngredient::factory()->for($recipe)->for($ingredient)->create([
            'family_id' => $family->id,
            'position' => 1,
            'quantity' => '280',
            'quantity_kind' => 'grams',
        ]);
        $tableNamesBefore = Schema::getTableListing();

        $this->actingAs($user)->post(route('simple-plan.selections.store'), [
            'recipe_id' => $recipe->id,
            'serving_count' => '2.5',
        ])->assertSessionHasNoErrors();

        $this->post(route('simple-plan.generate'))
            ->assertRedirect(route('simple-plan.generated'))
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Nákupní seznam byl vytvořen.',
            ]);
        foreach (range(1, 2) as $refresh) {
            $this->get(route('simple-plan.generated'))
                ->assertOk()
                ->assertInertia(fn (Assert $page): Assert => $page
                    ->component('simple-plan/Generated')
                    ->where('shoppingList.unplacedLines.0.ingredientName', 'Mouka')
                    ->where('shoppingList.unplacedLines.0.purchasePackages', '2')
                    ->where('shoppingList.unplacedLines.0.quantities.0.required.label', '175 g')
                    ->where('shoppingList.unplacedLines.0.contributions.0.recipeName', 'Lívance'));
        }

        $this->assertSame($tableNamesBefore, Schema::getTableListing());
        $this->assertFalse(Schema::hasTable('simple_plans'));
        $this->assertSame([(string) $recipe->id => '2.5'], session("meal_planning.simple_plan.{$family->id}"));
        $this->assertDatabaseCount('recipes', 1);
        $this->assertDatabaseCount('recipe_ingredients', 1);
    }

    public function test_generation_validation_failure_preserves_the_transient_plan_for_retry(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $recipe = Recipe::factory()->for($family)->create();

        $this->actingAs($user)->withSession([
            "meal_planning.simple_plan.{$family->id}" => [(string) $recipe->id => '1'],
        ])->post(route('simple-plan.generate'))
            ->assertSessionHasErrors(['plan' => 'Rychlý plán obsahuje recept bez surovin. Upravte recept a zkuste to znovu.']);

        $this->assertSame(
            [(string) $recipe->id => '1'],
            session("meal_planning.simple_plan.{$family->id}"),
        );
    }

    public function test_tampered_generation_state_cannot_read_a_recipe_from_another_family(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($user, $family);
        $foreignRecipe = Recipe::factory()->for($otherFamily)->create();
        $plan = [(string) $foreignRecipe->id => '1'];

        $this->actingAs($user)->withSession([
            "meal_planning.simple_plan.{$family->id}" => $plan,
        ])->post(route('simple-plan.generate'))
            ->assertSessionHasErrors([
                'plan' => 'Rychlý plán obsahuje recept, který už není v aktuální rodině dostupný.',
            ]);

        $this->assertSame($plan, session("meal_planning.simple_plan.{$family->id}"));
    }

    public function test_generation_projects_store_sections_metric_display_and_indexed_direct_alternatives(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $store = Store::factory()->for($family)->create(['name' => 'Albert']);
        $section = StoreSection::factory()->for($family)->create(['name' => 'Pečení']);
        $store->storeSections()->attach($section->id, ['position' => 3]);
        $ingredient = Ingredient::factory()->for($family)->create([
            'name' => 'Mouka',
            'weight_grams' => '2000',
            'store_id' => $store->id,
            'store_section_id' => $section->id,
        ]);
        $alternative = Ingredient::factory()->for($family)->create([
            'name' => 'Špaldová mouka',
            'weight_grams' => '1000',
        ]);
        DB::table('ingredient_alternatives')->insert([
            'family_id' => $family->id,
            'lower_ingredient_id' => min($ingredient->id, $alternative->id),
            'higher_ingredient_id' => max($ingredient->id, $alternative->id),
        ]);
        $recipe = Recipe::factory()->for($family)->create(['name' => 'Chléb', 'base_servings' => '1']);
        RecipeIngredient::factory()->for($recipe)->for($ingredient)->create([
            'family_id' => $family->id,
            'position' => 1,
            'quantity' => '1333.33',
            'quantity_kind' => 'grams',
        ]);

        $this->actingAs($user)->withSession([
            "meal_planning.simple_plan.{$family->id}" => [(string) $recipe->id => '1'],
        ])->post(route('simple-plan.generate'))
            ->assertRedirect(route('simple-plan.generated'));
        $this->get(route('simple-plan.generated'))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->where('shoppingList.storeGroups.0.storeName', 'Albert')
                ->where('shoppingList.storeGroups.0.sections.0.sectionName', 'Pečení')
                ->where('shoppingList.storeGroups.0.sections.0.lines.0.quantities.0.required.label', '1,33 kg')
                ->where('shoppingList.storeGroups.0.sections.0.lines.0.quantities.0.required.approximate', true)
                ->where('shoppingList.storeGroups.0.sections.0.lines.0.quantities.0.purchased.label', '2 kg')
                ->where('shoppingList.storeGroups.0.sections.0.lines.0.quantities.0.purchased.approximate', false)
                ->where('shoppingList.storeGroups.0.sections.0.lines.0.eligibleAlternatives.0.ingredientName', 'Špaldová mouka'));
    }

    public function test_every_calculation_problem_is_presented_and_the_plan_is_preserved(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $weightOnly = Ingredient::factory()->for($family)->create([
            'name' => 'Mouka',
            'weight_grams' => '500',
            'piece_count' => null,
        ]);
        $pieceOnly = Ingredient::factory()->for($family)->create([
            'name' => 'Vejce',
            'weight_grams' => null,
            'piece_count' => '10',
        ]);
        $firstRecipe = Recipe::factory()->for($family)->create(['name' => 'Omáčka']);
        $secondRecipe = Recipe::factory()->for($family)->create(['name' => 'Těsto']);
        RecipeIngredient::factory()->for($firstRecipe)->for($weightOnly)->create([
            'family_id' => $family->id,
            'position' => 1,
            'quantity' => '50',
            'quantity_kind' => 'millilitres',
        ]);
        RecipeIngredient::factory()->for($secondRecipe)->for($pieceOnly)->create([
            'family_id' => $family->id,
            'position' => 1,
            'quantity' => '100',
            'quantity_kind' => 'grams',
        ]);
        $plan = [
            (string) $firstRecipe->id => '2',
            (string) $secondRecipe->id => '2',
        ];

        $this->actingAs($user)->withSession([
            "meal_planning.simple_plan.{$family->id}" => $plan,
        ])->post(route('simple-plan.generate'))
            ->assertRedirect(route('simple-plan.generated'))
            ->assertInertiaFlash('toast', [
                'type' => 'error',
                'message' => 'Nákupní seznam vyžaduje opravy.',
            ]);
        $this->get(route('simple-plan.generated'))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->where('shoppingList', null)
                ->has('problems', 2)
                ->where('problems.0.message', 'Balení suroviny neobsahuje požadovaný druh množství.')
                ->where('problems.0.quantityLabel', '50 ml')
                ->where('problems.1.message', 'Balení suroviny neobsahuje požadovaný druh množství.'));

        $this->assertSame($plan, session("meal_planning.simple_plan.{$family->id}"));
    }

    public function test_direct_alternatives_recalculate_globally_and_can_be_reverted(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $first = Ingredient::factory()->for($family)->create(['name' => 'První mouka', 'weight_grams' => '100']);
        $second = Ingredient::factory()->for($family)->create(['name' => 'Druhá mouka', 'weight_grams' => '100']);
        $alternative = Ingredient::factory()->for($family)->create(['name' => 'Společná mouka', 'weight_grams' => '150']);
        foreach ([$first, $second] as $ingredient) {
            DB::table('ingredient_alternatives')->insert([
                'family_id' => $family->id,
                'lower_ingredient_id' => min($ingredient->id, $alternative->id),
                'higher_ingredient_id' => max($ingredient->id, $alternative->id),
            ]);
        }
        $recipes = [];
        foreach ([$first, $second] as $position => $ingredient) {
            $recipe = Recipe::factory()->for($family)->create(['base_servings' => '1']);
            RecipeIngredient::factory()->for($recipe)->for($ingredient)->create([
                'family_id' => $family->id,
                'position' => 1,
                'quantity' => '70',
                'quantity_kind' => 'grams',
            ]);
            $recipes[] = $recipe;
        }
        $plan = [
            (string) $recipes[0]->id => '1',
            (string) $recipes[1]->id => '1',
        ];

        $this->actingAs($user)->withSession([
            "meal_planning.simple_plan.{$family->id}" => $plan,
        ])->post(route('simple-plan.generate'))->assertSessionHasNoErrors();

        foreach ([$first, $second] as $ingredient) {
            $this->post(route('simple-plan.alternatives.store'), [
                'original_ingredient_id' => $ingredient->id,
                'alternative_ingredient_id' => $alternative->id,
            ])->assertSessionHasNoErrors()
                ->assertRedirect(route('simple-plan.generated'));
        }

        $this->get(route('simple-plan.generated'))->assertInertia(fn (Assert $page): Assert => $page
            ->has('shoppingList.unplacedLines', 1)
            ->where('shoppingList.unplacedLines.0.ingredientName', 'Společná mouka')
            ->where('shoppingList.unplacedLines.0.purchasePackages', '1')
            ->where('shoppingList.unplacedLines.0.quantities.0.required.label', '140 g')
            ->has('shoppingList.unplacedLines.0.alternativeChoices', 2));
        $this->assertSame([
            (string) $first->id => $alternative->id,
            (string) $second->id => $alternative->id,
        ], session("meal_planning.alternatives.{$family->id}"));

        $this->delete(route('simple-plan.alternatives.destroy', $first))
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Alternativa byla vrácena na původní surovinu.',
            ]);
        $this->get(route('simple-plan.generated'))->assertInertia(fn (Assert $page): Assert => $page
            ->has('shoppingList.unplacedLines', 2)
            ->where('shoppingList.unplacedLines.1.alternativeChoices.0.originalIngredientId', $second->id));
    }

    public function test_invalid_alternative_is_a_czech_field_error_and_keeps_the_previous_result(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($user, $family);
        $ingredient = Ingredient::factory()->for($family)->create(['weight_grams' => '100']);
        $foreignAlternative = Ingredient::factory()->for($otherFamily)->create(['weight_grams' => '100']);
        $recipe = Recipe::factory()->for($family)->create(['base_servings' => '1']);
        RecipeIngredient::factory()->for($recipe)->for($ingredient)->create([
            'family_id' => $family->id,
            'quantity' => '50',
            'quantity_kind' => 'grams',
        ]);

        $this->actingAs($user)->withSession([
            "meal_planning.simple_plan.{$family->id}" => [(string) $recipe->id => '1'],
        ])->post(route('simple-plan.generate'))->assertSessionHasNoErrors();
        $generated = session("meal_planning.generated.{$family->id}");

        $this->post(route('simple-plan.alternatives.store'), [
            'original_ingredient_id' => $ingredient->id,
            'alternative_ingredient_id' => $foreignAlternative->id,
        ])->assertSessionHasErrors([
            'alternative_ingredient_id' => 'Vybraná alternativa už není dostupná pro tuto surovinu.',
        ]);

        $this->assertSame($generated, session("meal_planning.generated.{$family->id}"));
        $this->assertFalse(session()->has("meal_planning.alternatives.{$family->id}"));

        $this->withSession([
            "meal_planning.alternatives.{$family->id}" => [$ingredient->id => $foreignAlternative->id],
        ])->post(route('simple-plan.generate'))->assertSessionHasErrors([
            'plan' => 'Jedna nebo více vybraných alternativ už není dostupná. Vraťte ji na původní surovinu a zkuste to znovu.',
        ]);
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
