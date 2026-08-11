<?php

declare(strict_types=1);

namespace Tests\Feature\Cookbook;

use App\Cookbook\Models\Ingredient;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class IngredientAlternativeTest extends TestCase
{
    public function test_direct_alternative_edges_are_symmetric_non_transitive_and_removable_from_either_side(): void
    {
        $user = User::factory()->create();
        $family = $this->family($user);
        $this->select($user, $family);
        $a = Ingredient::factory()->for($family)->create(['name' => 'A']);
        $b = Ingredient::factory()->for($family)->create(['name' => 'B']);
        $c = Ingredient::factory()->for($family)->create(['name' => 'C']);

        $this->actingAs($user)->post(route('ingredients.alternatives.store', $b), ['alternative_id' => $a->id])
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Alternativní surovina byla propojena.']);
        $this->actingAs($user)->post(route('ingredients.alternatives.store', $b), ['alternative_id' => $c->id])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ingredient_alternatives', [
            'family_id' => $family->id,
            'lower_ingredient_id' => $a->id,
            'higher_ingredient_id' => $b->id,
        ]);
        $this->actingAs($user)->get(route('ingredients.index', ['filter' => 'all']))->assertInertia(
            fn (AssertableInertia $page): AssertableInertia => $page
                ->where('ingredients.0.alternatives.0.id', $b->id)
                ->has('ingredients.0.alternatives', 1)
                ->has('ingredients.1.alternatives', 2)
                ->where('ingredients.2.alternatives.0.id', $b->id)
                ->has('ingredients.2.alternatives', 1),
        );

        $this->actingAs($user)->delete(route('ingredients.alternatives.destroy', [$b, $a]))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Propojení alternativních surovin bylo odebráno.']);
        $this->assertDatabaseMissing('ingredient_alternatives', [
            'lower_ingredient_id' => $a->id,
            'higher_ingredient_id' => $b->id,
        ]);
    }

    public function test_edges_reject_self_archived_duplicate_and_other_family_ingredients(): void
    {
        $user = User::factory()->create();
        $family = $this->family($user);
        $otherFamily = $this->family($user);
        $this->select($user, $family);
        $ingredient = Ingredient::factory()->for($family)->create();
        $alternative = Ingredient::factory()->for($family)->create();
        $archived = Ingredient::factory()->for($family)->create(['archived_at' => now()]);
        $foreign = Ingredient::factory()->for($otherFamily)->create();

        $this->actingAs($user)->post(route('ingredients.alternatives.store', $ingredient), ['alternative_id' => $ingredient->id])
            ->assertSessionHasErrors('alternative_id');
        $this->actingAs($user)->post(route('ingredients.alternatives.store', $ingredient), ['alternative_id' => $archived->id])
            ->assertSessionHasErrors('alternative_id');
        $this->actingAs($user)->post(route('ingredients.alternatives.store', $ingredient), ['alternative_id' => $foreign->id, 'family_id' => $otherFamily->id])
            ->assertSessionHasErrors('alternative_id');
        $this->actingAs($user)->post(route('ingredients.alternatives.store', $ingredient), ['alternative_id' => $alternative->id])
            ->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('ingredients.alternatives.store', $alternative), ['alternative_id' => $ingredient->id])
            ->assertSessionHasErrors('alternative_id');

        $this->assertDatabaseCount('ingredient_alternatives', 1);
    }

    public function test_database_rejects_cross_family_edges(): void
    {
        $family = $this->family();
        $otherFamily = $this->family();
        $lower = Ingredient::factory()->for($family)->create();
        $higher = Ingredient::factory()->for($otherFamily)->create();

        $this->expectException(QueryException::class);

        DB::table('ingredient_alternatives')->insert([
            'family_id' => $family->id,
            'lower_ingredient_id' => $lower->id,
            'higher_ingredient_id' => $higher->id,
        ]);
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
