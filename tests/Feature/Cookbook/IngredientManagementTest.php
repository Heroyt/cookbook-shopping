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

final class IngredientManagementTest extends TestCase
{
    public function test_guests_cannot_list_or_create_ingredients(): void
    {
        $this->get(route('ingredients.index'))->assertRedirect(route('login'));
        $this->post(route('ingredients.store'), [
            'name' => 'Rýže',
            'weight_grams' => '500',
        ])->assertRedirect(route('login'));
    }

    public function test_each_member_can_create_and_list_concrete_ingredients_in_their_shared_family(): void
    {
        $firstMember = User::factory()->create();
        $secondMember = User::factory()->create();
        $family = $this->createFamilyWithMembers($firstMember, $secondMember);
        $this->selectCurrentFamily($firstMember, $family);
        $this->selectCurrentFamily($secondMember, $family);

        $this
            ->actingAs($firstMember)
            ->post(route('ingredients.store'), [
                'name' => '  Celozrnný   chléb  ',
                'weight_grams' => '1100',
                'volume_millilitres' => null,
                'piece_count' => '10',
            ])
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Surovina byla vytvořena.',
            ])
            ->assertRedirect(route('ingredients.index'));

        $this->assertDatabaseHas('ingredients', [
            'family_id' => $family->id,
            'name' => 'Celozrnný chléb',
            'normalized_name' => 'celozrnný chléb',
            'weight_grams' => 1100,
            'volume_millilitres' => null,
            'piece_count' => 10,
        ]);

        $this
            ->actingAs($secondMember)
            ->post(route('ingredients.store'), [
                'name' => 'Vejce',
                'weight_grams' => null,
                'volume_millilitres' => null,
                'piece_count' => '12.5',
            ])
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Surovina byla vytvořena.',
            ])
            ->assertRedirect(route('ingredients.index'));

        $this->assertDatabaseHas('ingredients', [
            'family_id' => $family->id,
            'name' => 'Vejce',
            'weight_grams' => null,
            'volume_millilitres' => null,
            'piece_count' => 12.5,
        ]);

        $this
            ->actingAs($firstMember)
            ->get(route('ingredients.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->component('ingredients/Index')
                ->has('ingredients', 2)
                ->where('ingredients.0.name', 'Celozrnný chléb')
                ->where('ingredients.0.quantities', ['1,1 kg', '10 ks'])
                ->where('ingredients.1.name', 'Vejce')
                ->where('ingredients.1.quantities', ['12,5 ks']));
    }

    public function test_ingredient_reads_and_writes_are_scoped_to_the_current_family(): void
    {
        $user = User::factory()->create();
        $currentFamily = $this->createFamilyWithMembers($user);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($user, $currentFamily);
        $visibleIngredient = Ingredient::factory()->for($currentFamily)->create(['name' => 'Viditelná rýže']);
        Ingredient::factory()->for($otherFamily)->create(['name' => 'Soukromá rýže']);

        $this
            ->actingAs($user)
            ->post(route('ingredients.store'), [
                'name' => 'Nová rýže',
                'weight_grams' => '500',
                'family_id' => $otherFamily->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ingredients', [
            'family_id' => $currentFamily->id,
            'name' => 'Nová rýže',
        ]);
        $this->assertDatabaseMissing('ingredients', [
            'family_id' => $otherFamily->id,
            'name' => 'Nová rýže',
        ]);

        $this
            ->actingAs($user)
            ->get(route('ingredients.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('ingredients', 2)
                ->where('ingredients.0.name', 'Nová rýže')
                ->where('ingredients.1.id', $visibleIngredient->id)
                ->where('ingredients.1.name', 'Viditelná rýže'));
    }

    public function test_normalized_ingredient_name_is_unique_within_a_family_and_race_is_a_field_error(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        Ingredient::factory()->for($family)->create(['name' => 'Celozrnný chléb']);

        $this
            ->actingAs($user)
            ->post(route('ingredients.store'), [
                'name' => '  CELOZRNNÝ   chléb ',
                'weight_grams' => '500',
            ])
            ->assertSessionHasErrors([
                'name' => 'Surovina s tímto názvem už v aktuální rodině existuje.',
            ]);

        $this->assertDatabaseCount('ingredients', 1);
    }

    public function test_same_normalized_ingredient_name_is_allowed_in_another_family(): void
    {
        $user = User::factory()->create();
        $currentFamily = $this->createFamilyWithMembers($user);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($user, $currentFamily);
        Ingredient::factory()->for($otherFamily)->create(['name' => 'Rýže']);

        $this
            ->actingAs($user)
            ->post(route('ingredients.store'), [
                'name' => 'RÝŽE',
                'weight_grams' => '500',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ingredients', [
            'family_id' => $currentFamily->id,
            'normalized_name' => 'rýže',
        ]);
    }

    public function test_ingredient_name_and_package_quantities_are_validated(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);

        $this->actingAs($user)->post(route('ingredients.store'), [
            'name' => '   ',
            'weight_grams' => '500',
        ])->assertSessionHasErrors('name');
        $this->actingAs($user)->post(route('ingredients.store'), [
            'name' => str_repeat('a', 256),
            'weight_grams' => '500',
        ])->assertSessionHasErrors('name');
        $this->actingAs($user)->post(route('ingredients.store'), [
            'name' => ['Rýže'],
            'weight_grams' => '500',
        ])->assertSessionHasErrors('name');
        $this->actingAs($user)->post(route('ingredients.store'), [
            'name' => 'Rýže',
        ])->assertSessionHasErrors([
            'quantities' => 'Zadejte hmotnost, objem nebo počet kusů v balení.',
        ]);
        $this->actingAs($user)->post(route('ingredients.store'), [
            'name' => 'Rýže',
            'weight_grams' => '500',
            'volume_millilitres' => '500',
        ])->assertSessionHasErrors([
            'weight_grams' => 'Hmotnost a objem balení nelze zadat současně.',
            'volume_millilitres' => 'Hmotnost a objem balení nelze zadat současně.',
        ]);

        foreach (['0', '-1', '1.1234567', '100000000000000', ['500']] as $invalidQuantity) {
            $this
                ->actingAs($user)
                ->post(route('ingredients.store'), [
                    'name' => 'Rýže',
                    'weight_grams' => $invalidQuantity,
                ])
                ->assertSessionHasErrors('weight_grams');
        }

        $this->assertDatabaseCount('ingredients', 0);
    }

    public function test_canonical_package_quantities_are_displayed_in_derived_units(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        Ingredient::factory()->for($family)->create([
            'name' => 'Mouka',
            'weight_grams' => '999.125',
            'piece_count' => null,
        ]);
        Ingredient::factory()->for($family)->create([
            'name' => 'Mléko',
            'weight_grams' => null,
            'volume_millilitres' => '1500',
            'piece_count' => '2.5',
        ]);

        $this
            ->actingAs($user)
            ->get(route('ingredients.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('ingredients.0.name', 'Mléko')
                ->where('ingredients.0.quantities', ['1,5 l', '2,5 ks'])
                ->where('ingredients.1.name', 'Mouka')
                ->where('ingredients.1.quantities', ['999,13 g']));
    }

    public function test_database_rejects_invalid_package_quantity_combinations(): void
    {
        $family = Family::factory()->create();

        try {
            DB::table('ingredients')->insert([
                'family_id' => $family->id,
                'name' => 'Bez množství',
                'normalized_name' => 'bez množství',
            ]);
            $this->fail('The database accepted an Ingredient without a package quantity.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }

        try {
            DB::table('ingredients')->insert([
                'family_id' => $family->id,
                'name' => 'Dvě míry',
                'normalized_name' => 'dvě míry',
                'weight_grams' => '500',
                'volume_millilitres' => '500',
            ]);
            $this->fail('The database accepted weight and volume together.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }

        try {
            DB::table('ingredients')->insert([
                'family_id' => $family->id,
                'name' => 'Nulová míra',
                'normalized_name' => 'nulová míra',
                'weight_grams' => '0',
            ]);
            $this->fail('The database accepted a non-positive package quantity.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_ingredient_management_requires_a_current_family(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('ingredients.index'))->assertNotFound();
        $this->actingAs($user)->post(route('ingredients.store'), [
            'name' => 'Rýže',
            'weight_grams' => '500',
        ])->assertNotFound();

        $this->assertDatabaseCount('ingredients', 0);
    }

    private function createFamilyWithMembers(User ...$users): Family
    {
        $family = Family::factory()->create();

        foreach ($users as $user) {
            FamilyMembership::factory()->create([
                'family_id' => $family->id,
                'user_id' => $user->id,
            ]);
        }

        return $family;
    }

    private function selectCurrentFamily(User $user, Family $family): void
    {
        $user->forceFill(['current_family_id' => $family->id])->save();
    }
}
