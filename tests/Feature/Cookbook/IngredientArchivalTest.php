<?php

declare(strict_types=1);

namespace Tests\Feature\Cookbook;

use App\Cookbook\Models\Ingredient;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class IngredientArchivalTest extends TestCase
{
    public function test_equal_members_can_archive_filter_and_restore_an_ingredient(): void
    {
        $firstMember = User::factory()->create();
        $secondMember = User::factory()->create();
        $family = $this->createFamilyWithMembers($firstMember, $secondMember);
        $this->selectCurrentFamily($firstMember, $family);
        $this->selectCurrentFamily($secondMember, $family);
        $ingredient = Ingredient::factory()->for($family)->create(['name' => 'Rýže']);

        $this->actingAs($secondMember)
            ->patch(route('ingredients.archive', $ingredient))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Surovina byla archivována.'])
            ->assertRedirect(route('ingredients.index'));

        $this->assertNotNull($ingredient->fresh()->archived_at);
        $this->actingAs($firstMember)->get(route('ingredients.index'))->assertInertia(
            fn (AssertableInertia $page): AssertableInertia => $page
                ->where('filter', 'active')
                ->has('ingredients', 0),
        );
        $this->actingAs($firstMember)->get(route('ingredients.index', ['filter' => 'archived']))->assertInertia(
            fn (AssertableInertia $page): AssertableInertia => $page
                ->where('filter', 'archived')
                ->where('ingredients.0.id', $ingredient->id)
                ->where('ingredients.0.archived', true),
        );
        $this->actingAs($firstMember)->get(route('ingredients.index', ['filter' => 'all']))->assertInertia(
            fn (AssertableInertia $page): AssertableInertia => $page
                ->where('filter', 'all')
                ->where('ingredients.0.id', $ingredient->id),
        );

        $this->actingAs($firstMember)
            ->patch(route('ingredients.restore', $ingredient))
            ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Surovina byla obnovena.'])
            ->assertRedirect(route('ingredients.index'));

        $this->assertNull($ingredient->fresh()->archived_at);
    }

    public function test_an_archived_ingredient_must_be_restored_before_editing(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $ingredient = Ingredient::factory()->for($family)->create([
            'name' => 'Rýže',
            'archived_at' => now(),
        ]);

        $this->actingAs($user)->patch(route('ingredients.update', $ingredient), [
            'name' => 'Jasmínová rýže',
            'metric_quantity' => '500',
            'metric_unit' => 'g',
        ])->assertSessionHasErrors([
            'ingredient' => 'Před úpravou surovinu obnovte z archivu.',
        ]);

        $this->assertDatabaseHas('ingredients', ['id' => $ingredient->id, 'name' => 'Rýže']);
    }

    public function test_archive_and_restore_are_current_family_only_and_filter_is_validated(): void
    {
        $user = User::factory()->create();
        $currentFamily = $this->createFamilyWithMembers($user);
        $otherFamily = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $currentFamily);
        $otherIngredient = Ingredient::factory()->for($otherFamily)->create();

        $this->actingAs($user)->patch(route('ingredients.archive', $otherIngredient), [
            'family_id' => $otherFamily->id,
        ])->assertNotFound();
        $this->actingAs($user)->patch(route('ingredients.restore', $otherIngredient))->assertNotFound();
        $this->actingAs($user)->get(route('ingredients.index', ['filter' => 'deleted']))->assertSessionHasErrors('filter');

        $this->assertNull($otherIngredient->fresh()->archived_at);
    }

    public function test_archival_keeps_the_normalized_name_reserved(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $ingredient = Ingredient::factory()->for($family)->create(['name' => 'Rýže', 'archived_at' => now()]);

        $this->actingAs($user)->post(route('ingredients.store'), [
            'name' => ' RÝŽE ',
            'metric_quantity' => '500',
            'metric_unit' => 'g',
        ])->assertSessionHasErrors('name');

        $this->assertDatabaseCount('ingredients', 1);
    }

    private function createFamilyWithMembers(User ...$users): Family
    {
        $family = Family::factory()->create();

        foreach ($users as $user) {
            FamilyMembership::factory()->create(['family_id' => $family->id, 'user_id' => $user->id]);
        }

        return $family;
    }

    private function selectCurrentFamily(User $user, Family $family): void
    {
        $user->forceFill(['current_family_id' => $family->id])->save();
    }
}
