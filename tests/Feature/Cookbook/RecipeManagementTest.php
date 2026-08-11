<?php

declare(strict_types=1);

namespace Tests\Feature\Cookbook;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\RecipeIngredient;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Tests\TestCase;

final class RecipeManagementTest extends TestCase
{
    public function test_guests_cannot_manage_recipes(): void
    {
        $this->get(route('recipes.index'))->assertRedirect(route('login'));
        $this->post(route('recipes.store'))->assertRedirect(route('login'));
    }

    public function test_each_member_can_create_a_complete_ordered_recipe_aggregate_for_the_current_family(): void
    {
        $firstMember = User::factory()->create();
        $secondMember = User::factory()->create();
        $family = $this->createFamilyWithMembers($firstMember, $secondMember);
        $this->selectCurrentFamily($firstMember, $family);
        $this->selectCurrentFamily($secondMember, $family);
        $flour = Ingredient::factory()->for($family)->create(['name' => 'Mouka', 'weight_grams' => 1000]);

        $response = $this->actingAs($secondMember)->post(route('recipes.store'), [
            'name' => '  Lívance  ',
            'base_servings' => '4',
            'source_url' => 'https://example.test/livance',
            'preparation_minutes' => 15,
            'cooking_minutes' => 20,
            'notes' => 'Podávejte teplé.',
            'ingredients' => [
                ['ingredient_id' => $flour->id, 'quantity' => '250', 'quantity_kind' => 'grams'],
                ['ingredient_id' => $flour->id, 'quantity' => '25', 'quantity_kind' => 'grams'],
            ],
            'steps' => ['Smíchejte těsto.', 'Usmažte lívance.'],
            'tag_ids' => [],
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Recept byl vytvořen.'])
            ->assertRedirect(route('recipes.index'));

        $this->assertDatabaseHas('recipes', [
            'family_id' => $family->id,
            'name' => 'Lívance',
            'normalized_name' => 'lívance',
            'base_servings' => 4,
            'version' => 1,
        ]);
        $recipeId = (int) $this->getConnection()->table('recipes')->value('id');
        $this->assertDatabaseHas('recipe_ingredients', [
            'recipe_id' => $recipeId, 'ingredient_id' => $flour->id, 'position' => 1, 'quantity' => 250,
        ]);
        $this->assertDatabaseHas('recipe_ingredients', [
            'recipe_id' => $recipeId, 'ingredient_id' => $flour->id, 'position' => 2, 'quantity' => 25,
        ]);
        $this->assertDatabaseHas('recipe_steps', ['recipe_id' => $recipeId, 'position' => 1, 'instruction' => 'Smíchejte těsto.']);
        $this->assertDatabaseHas('recipe_steps', ['recipe_id' => $recipeId, 'position' => 2, 'instruction' => 'Usmažte lívance.']);
    }

    public function test_recipe_save_rejects_foreign_ingredients_and_rolls_back_the_whole_aggregate(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($user, $family);
        $foreignIngredient = Ingredient::factory()->for($otherFamily)->create();

        $this->actingAs($user)->post(route('recipes.store'), [
            'name' => 'Cizí recept',
            'base_servings' => '2',
            'ingredients' => [[
                'ingredient_id' => $foreignIngredient->id,
                'quantity' => '1',
                'quantity_kind' => 'grams',
            ]],
            'steps' => [],
            'tag_ids' => [],
        ])->assertSessionHasErrors(['ingredients.0.ingredient_id' => 'Vybraná surovina není dostupná v aktuální rodině.']);

        $this->assertDatabaseCount('recipes', 0);
        $this->assertDatabaseCount('recipe_ingredients', 0);
    }

    public function test_complete_update_replaces_the_aggregate_and_stale_version_rejects_without_partial_changes(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $flour = Ingredient::factory()->for($family)->create(['name' => 'Mouka', 'weight_grams' => 1000]);

        $this->actingAs($user)->post(route('recipes.store'), [
            'name' => 'Těsto', 'base_servings' => '2',
            'ingredients' => [['ingredient_id' => $flour->id, 'quantity' => '200', 'quantity_kind' => 'grams']],
            'steps' => ['Smíchejte.'], 'tag_ids' => [],
        ])->assertSessionHasNoErrors();
        $recipeId = (int) $this->getConnection()->table('recipes')->value('id');

        $this->actingAs($user)->put(route('recipes.update', $recipeId), [
            'version' => 1, 'name' => 'Jemné těsto', 'base_servings' => '3',
            'ingredients' => [
                ['ingredient_id' => $flour->id, 'quantity' => '300', 'quantity_kind' => 'grams'],
                ['ingredient_id' => $flour->id, 'quantity' => '30', 'quantity_kind' => 'grams'],
            ],
            'steps' => ['Promíchejte.', 'Nechte odležet.'], 'tag_ids' => [],
        ])->assertSessionHasNoErrors()->assertInertiaFlash('toast', [
            'type' => 'success', 'message' => 'Recept byl uložen.',
        ]);

        $this->assertDatabaseHas('recipes', ['id' => $recipeId, 'name' => 'Jemné těsto', 'version' => 2, 'base_servings' => 3]);
        $this->assertDatabaseCount('recipe_ingredients', 2);
        $this->assertDatabaseCount('recipe_steps', 2);

        $this->actingAs($user)->put(route('recipes.update', $recipeId), [
            'version' => 1, 'name' => 'Přepsané těsto', 'base_servings' => '1',
            'ingredients' => [['ingredient_id' => $flour->id, 'quantity' => '1', 'quantity_kind' => 'grams']],
            'steps' => [], 'tag_ids' => [],
        ])->assertSessionHasErrors(['version' => 'Recept byl mezitím změněn. Zkontrolujte aktuální podobu a zkuste to znovu.']);

        $this->assertDatabaseHas('recipes', ['id' => $recipeId, 'name' => 'Jemné těsto', 'version' => 2]);
        $this->assertDatabaseCount('recipe_ingredients', 2);
        $this->assertDatabaseCount('recipe_steps', 2);
    }

    public function test_recipe_update_is_current_family_scoped(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($user, $family);
        $foreignRecipe = Recipe::factory()->for($otherFamily)->create();
        $ingredient = Ingredient::factory()->for($family)->create();

        $this->actingAs($user)->put(route('recipes.update', $foreignRecipe), [
            'version' => 1, 'name' => 'Cizí recept', 'base_servings' => '2',
            'ingredients' => [['ingredient_id' => $ingredient->id, 'quantity' => '1', 'quantity_kind' => 'grams']],
            'steps' => [], 'tag_ids' => [],
        ])->assertNotFound();
    }

    public function test_canonical_servings_line_quantities_and_complete_nutrition_override_are_validated(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $ingredient = Ingredient::factory()->for($family)->create(['weight_grams' => 1000, 'piece_count' => null]);

        $this->actingAs($user)->post(route('recipes.store'), [
            'name' => 'Neplatný recept', 'base_servings' => '0',
            'ingredients' => [[
                'ingredient_id' => $ingredient->id, 'quantity' => '1.1234567', 'quantity_kind' => 'piece',
            ]],
            'nutrition_energy_kcal' => '100',
        ])->assertSessionHasErrors(['base_servings', 'ingredients.0.quantity', 'nutrition']);

        $this->assertDatabaseCount('recipes', 0);
    }

    public function test_normalized_duplicate_and_failed_update_roll_back_every_aggregate_child_change(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $ingredient = Ingredient::factory()->for($family)->create(['weight_grams' => 1000]);
        Recipe::factory()->for($family)->create(['name' => 'Koláč']);
        $recipe = Recipe::factory()->for($family)->create(['name' => 'Těsto']);
        RecipeIngredient::query()->create([
            'family_id' => $family->id, 'recipe_id' => $recipe->id, 'ingredient_id' => $ingredient->id,
            'position' => 1, 'quantity' => 100, 'quantity_kind' => 'grams',
        ]);

        $this->actingAs($user)->put(route('recipes.update', $recipe), [
            'version' => 1, 'name' => '  KOLÁČ ', 'base_servings' => '8',
            'ingredients' => [['ingredient_id' => $ingredient->id, 'quantity' => '900', 'quantity_kind' => 'grams']],
        ])->assertSessionHasErrors(['name' => 'Recept s tímto názvem už v aktuální rodině existuje.']);

        $this->assertDatabaseHas('recipes', ['id' => $recipe->id, 'name' => 'Těsto', 'version' => 1]);
        $this->assertDatabaseHas('recipe_ingredients', ['recipe_id' => $recipe->id, 'quantity' => 100]);
        $this->assertDatabaseMissing('recipe_ingredients', ['recipe_id' => $recipe->id, 'quantity' => 900]);
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
