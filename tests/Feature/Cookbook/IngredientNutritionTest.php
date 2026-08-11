<?php

declare(strict_types=1);

namespace Tests\Feature\Cookbook;

use App\Cookbook\Models\Ingredient;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class IngredientNutritionTest extends TestCase
{
    public function test_a_member_can_save_list_and_remove_a_complete_nutrition_profile(): void
    {
        $user = User::factory()->create();
        $family = $this->family($user);
        $this->select($user, $family);
        $ingredient = Ingredient::factory()->for($family)->create(['weight_grams' => 500]);

        $this->actingAs($user)->patch(route('ingredients.update', $ingredient), $this->payload($ingredient, [
            'nutrition_basis_kind' => 'grams',
            'nutrition_basis_quantity' => '100',
            'nutrition_energy_kcal' => '360',
            'nutrition_fat_grams' => '1.2',
            'nutrition_protein_grams' => '7.5',
            'nutrition_carbohydrate_grams' => '78',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ingredient_nutrition_profiles', [
            'ingredient_id' => $ingredient->id,
            'basis_kind' => 'grams',
            'basis_quantity' => 100,
            'energy_kcal' => 360,
        ]);
        $this->actingAs($user)->get(route('ingredients.index'))->assertInertia(
            fn (AssertableInertia $page): AssertableInertia => $page
                ->where('ingredients.0.nutrition.basisKind', 'grams')
                ->where('ingredients.0.nutrition.basisQuantity', '100.000000')
                ->where('ingredients.0.nutrition.energyKcal', '360.000000'),
        );

        $this->actingAs($user)->patch(route('ingredients.update', $ingredient), $this->payload($ingredient))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('ingredient_nutrition_profiles', ['ingredient_id' => $ingredient->id]);
    }

    public function test_nutrition_is_all_or_none_positive_and_compatible_with_the_package(): void
    {
        $user = User::factory()->create();
        $family = $this->family($user);
        $this->select($user, $family);
        $ingredient = Ingredient::factory()->for($family)->create(['weight_grams' => 500, 'piece_count' => null]);

        $this->actingAs($user)->patch(route('ingredients.update', $ingredient), $this->payload($ingredient, [
            'nutrition_basis_kind' => 'grams',
            'nutrition_basis_quantity' => '100',
        ]))->assertSessionHasErrors('nutrition');
        $this->actingAs($user)->patch(route('ingredients.update', $ingredient), $this->payload($ingredient, [
            'nutrition_basis_kind' => 'piece',
            'nutrition_basis_quantity' => '1',
            'nutrition_energy_kcal' => '10',
            'nutrition_fat_grams' => '0',
            'nutrition_protein_grams' => '1',
            'nutrition_carbohydrate_grams' => '1',
        ]))->assertSessionHasErrors('nutrition_basis_kind');
        $this->actingAs($user)->patch(route('ingredients.update', $ingredient), $this->payload($ingredient, [
            'nutrition_basis_kind' => 'package',
            'nutrition_basis_quantity' => '2',
            'nutrition_energy_kcal' => '10',
            'nutrition_fat_grams' => '0',
            'nutrition_protein_grams' => '1',
            'nutrition_carbohydrate_grams' => '1',
        ]))->assertSessionHasErrors('nutrition_basis_quantity');
    }

    public function test_a_quantity_kind_cannot_be_removed_while_the_saved_nutrition_basis_depends_on_it(): void
    {
        $user = User::factory()->create();
        $family = $this->family($user);
        $this->select($user, $family);
        $ingredient = Ingredient::factory()->for($family)->create(['weight_grams' => 500, 'piece_count' => 10]);
        $ingredient->nutritionProfile()->create([
            'basis_kind' => 'piece', 'basis_quantity' => 1, 'energy_kcal' => 10,
            'fat_grams' => 0, 'protein_grams' => 1, 'carbohydrate_grams' => 1,
        ]);

        $this->actingAs($user)->patch(route('ingredients.update', $ingredient), [
            'name' => $ingredient->name,
            'metric_quantity' => '500',
            'metric_unit' => 'g',
            'nutrition_basis_kind' => 'piece',
            'nutrition_basis_quantity' => '1',
            'nutrition_energy_kcal' => '10',
            'nutrition_fat_grams' => '0',
            'nutrition_protein_grams' => '1',
            'nutrition_carbohydrate_grams' => '1',
        ])->assertSessionHasErrors([
            'piece_count' => 'Počet kusů nelze odebrat, protože jej používá nutriční profil.',
        ]);
    }

    public function test_application_rejects_an_invalid_nutrition_profile(): void
    {
        $user = User::factory()->create();
        $family = $this->family($user);
        $this->select($user, $family);
        $ingredient = Ingredient::factory()->for($family)->create();

        $this->actingAs($user)->patch(route('ingredients.update', $ingredient), $this->payload($ingredient, [
            'nutrition_basis_kind' => 'package',
            'nutrition_basis_quantity' => '2',
            'nutrition_energy_kcal' => '-1',
            'nutrition_fat_grams' => '0',
            'nutrition_protein_grams' => '0',
            'nutrition_carbohydrate_grams' => '0',
        ]))->assertSessionHasErrors(['nutrition_basis_quantity', 'nutrition_energy_kcal']);

        $this->assertDatabaseMissing('ingredient_nutrition_profiles', ['ingredient_id' => $ingredient->id]);
    }

    /** @param array<string, string> $extra */
    private function payload(Ingredient $ingredient, array $extra = []): array
    {
        return ['name' => $ingredient->name, 'metric_quantity' => '500', 'metric_unit' => 'g', ...$extra];
    }

    private function family(User ...$users): Family
    {
        $family = Family::factory()->create();
        foreach ($users as $user) {
            FamilyMembership::factory()->create(['family_id' => $family->id, 'user_id' => $user->id]);
        }

        return $family;
    }

    private function select(User $user, Family $family): void
    {
        $user->forceFill(['current_family_id' => $family->id])->save();
    }
}
