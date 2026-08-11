<?php

declare(strict_types=1);

namespace Tests\Feature\Cookbook;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\RecipeIngredient;
use App\Cookbook\Models\RecipeTag;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class RecipeDiscoveryTest extends TestCase
{
    public function test_recipe_tags_are_normalized_family_scoped_and_can_be_reused_after_cleanup(): void
    {
        [$user, $family] = $this->currentFamilyUser();

        $this->actingAs($user)->post(route('recipe-tags.store'), ['name' => '  Rychlé  '])
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Štítek receptu byl vytvořen.']);
        $tag = RecipeTag::query()->firstOrFail();
        $this->assertSame('rychlé', $tag->normalized_name);

        $this->actingAs($user)->post(route('recipe-tags.store'), ['name' => 'RYCHLÉ'])
            ->assertSessionHasErrors(['name' => 'Štítek receptu s tímto názvem už v aktuální rodině existuje.']);

        $recipe = Recipe::factory()->for($family)->create();
        $recipe->tags()->attach($tag->id, ['family_id' => $family->id]);
        $this->actingAs($user)->delete(route('recipe-tags.destroy', $tag))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('recipe_recipe_tag', ['recipe_tag_id' => $tag->id]);

        $this->actingAs($user)->post(route('recipe-tags.store'), ['name' => 'rychlé'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('recipe_tags', 1);
    }

    public function test_recipes_can_be_archived_filtered_and_restored_by_any_member(): void
    {
        [$firstMember, $family] = $this->currentFamilyUser();
        $secondMember = User::factory()->create();
        FamilyMembership::factory()->for($family)->for($secondMember)->create();
        $secondMember->forceFill(['current_family_id' => $family->id])->save();
        $recipe = Recipe::factory()->for($family)->create(['name' => 'Lívance']);

        $this->actingAs($secondMember)->patch(route('recipes.archive', $recipe))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Recept byl archivován.']);

        $this->actingAs($firstMember)->get(route('recipes.index'))->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->has('recipes', 0)->where('filter', 'active'));
        $this->actingAs($firstMember)->get(route('recipes.index', ['filter' => 'archived']))->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->has('recipes', 1)->where('recipes.0.archived', true)->where('filter', 'archived'));

        $this->actingAs($firstMember)->patch(route('recipes.restore', $recipe))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Recept byl obnoven.']);
        $this->assertDatabaseHas('recipes', ['id' => $recipe->id, 'archived_at' => null]);
    }

    public function test_layered_search_deduplicates_recipes_and_reports_every_match_reason_with_family_isolation(): void
    {
        [$user, $family] = $this->currentFamilyUser();
        $otherFamily = Family::factory()->create();
        $tomato = Ingredient::factory()->for($family)->create(['name' => 'Rajče']);
        $tag = RecipeTag::factory()->for($family)->create(['name' => 'Rajčatové']);
        $recipe = Recipe::factory()->for($family)->create(['name' => 'Rajčatová polévka']);
        RecipeIngredient::query()->create([
            'family_id' => $family->id, 'recipe_id' => $recipe->id, 'ingredient_id' => $tomato->id,
            'position' => 1, 'quantity' => 100, 'quantity_kind' => 'grams',
        ]);
        $recipe->tags()->attach($tag->id, ['family_id' => $family->id]);
        Recipe::factory()->for($otherFamily)->create(['name' => 'Rajčatová omáčka']);

        $this->actingAs($user)->get(route('recipes.index', ['search' => '  RAJ  ']))
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('recipes', 1)
                ->where('recipes.0.id', $recipe->id)
                ->where('recipes.0.matchReasons', [
                    ['kind' => 'name', 'label' => 'Název receptu'],
                    ['kind' => 'tag', 'label' => 'Štítek: Rajčatové'],
                    ['kind' => 'ingredient', 'label' => 'Surovina: Rajče'],
                ])
                ->where('search', 'RAJ'));
    }

    /** @return array{User, Family} */
    private function currentFamilyUser(): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create();
        FamilyMembership::factory()->for($family)->for($user)->create();
        $user->forceFill(['current_family_id' => $family->id])->save();

        return [$user, $family];
    }
}
