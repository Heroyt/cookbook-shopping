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

final class RecipeIngredientDependencyTest extends TestCase
{
    public function test_an_ingredient_package_kind_used_by_a_recipe_cannot_be_removed(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create();
        FamilyMembership::factory()->for($family)->for($user)->create();
        $user->forceFill(['current_family_id' => $family->id])->save();
        $ingredient = Ingredient::factory()->for($family)->create(['weight_grams' => 1000, 'piece_count' => 10]);
        $recipe = Recipe::factory()->for($family)->create();
        RecipeIngredient::query()->create([
            'family_id' => $family->id, 'recipe_id' => $recipe->id, 'ingredient_id' => $ingredient->id,
            'position' => 1, 'quantity' => 100, 'quantity_kind' => 'grams',
        ]);

        $this->actingAs($user)->patch(route('ingredients.update', $ingredient), [
            'name' => $ingredient->name,
            'piece_count' => '10',
        ])->assertSessionHasErrors([
            'metric_quantity' => 'Metrické množství nelze odebrat, protože jej používá surovina v receptu.',
        ]);

        $this->assertDatabaseHas('ingredients', ['id' => $ingredient->id, 'weight_grams' => 1000]);
    }
}
