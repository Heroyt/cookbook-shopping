<?php

declare(strict_types=1);

namespace Tests\Feature\Cookbook;

use App\Cookbook\Models\Ingredient;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class IngredientManagementTest extends TestCase
{
    public function test_guests_cannot_list_or_create_ingredients(): void
    {
        $this->get(route('ingredients.index'))->assertRedirect(route('login'));
        $this->post(route('ingredients.store'), [
            'name' => 'Rýže',
            'metric_quantity' => '500',
            'metric_unit' => 'g',
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
                'metric_quantity' => '1.1',
                'metric_unit' => 'kg',
                'piece_count' => '10',
                'description' => 'Balený krájený chléb.',
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
            'description' => 'Balený krájený chléb.',
            'weight_grams' => 1100,
            'volume_millilitres' => null,
            'piece_count' => 10,
        ]);

        $this
            ->actingAs($secondMember)
            ->post(route('ingredients.store'), [
                'name' => 'Vejce',
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
                ->where('ingredients.0.description', 'Balený krájený chléb.')
                ->where('ingredients.0.metricQuantity', '1100.000000')
                ->where('ingredients.0.metricUnit', 'g')
                ->where('ingredients.0.pieceCount', '10.000000')
                ->where('ingredients.0.quantities', ['1,1 kg', '10 ks'])
                ->where('ingredients.1.name', 'Vejce')
                ->where('ingredients.1.quantities', ['12,5 ks']));
    }

    public function test_each_member_can_edit_an_ingredient_with_explicit_metric_input_units_and_description(): void
    {
        $firstMember = User::factory()->create();
        $secondMember = User::factory()->create();
        $family = $this->createFamilyWithMembers($firstMember, $secondMember);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($firstMember, $family);
        $this->selectCurrentFamily($secondMember, $family);
        $ingredient = Ingredient::factory()->for($family)->create([
            'name' => 'Mléko',
            'weight_grams' => '1000',
        ]);

        $this
            ->actingAs($secondMember)
            ->patch(route('ingredients.update', $ingredient), [
                'name' => '  Plnotučné   mléko ',
                'metric_quantity' => '1.5',
                'metric_unit' => 'l',
                'piece_count' => '2',
                'description' => 'Trvanlivé mléko v kartonu.',
                'family_id' => $otherFamily->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Surovina byla upravena.',
            ])
            ->assertRedirect(route('ingredients.index'));

        $this->assertDatabaseHas('ingredients', [
            'id' => $ingredient->id,
            'family_id' => $family->id,
            'name' => 'Plnotučné mléko',
            'normalized_name' => 'plnotučné mléko',
            'description' => 'Trvanlivé mléko v kartonu.',
            'weight_grams' => null,
            'volume_millilitres' => 1500,
            'piece_count' => 2,
        ]);
    }

    public function test_ingredient_updates_are_current_family_scoped_and_normalized_duplicates_are_field_errors(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($user, $family);
        $ingredient = Ingredient::factory()->for($family)->create(['name' => 'Mouka']);
        Ingredient::factory()->for($family)->create(['name' => 'Rýže']);
        $foreignIngredient = Ingredient::factory()->for($otherFamily)->create(['name' => 'Olej']);

        $this
            ->actingAs($user)
            ->patch(route('ingredients.update', $foreignIngredient), [
                'name' => 'Cizí olej',
                'metric_quantity' => '1',
                'metric_unit' => 'l',
            ])
            ->assertNotFound();

        $this
            ->actingAs($user)
            ->patch(route('ingredients.update', $ingredient), [
                'name' => '  RÝŽE ',
                'metric_quantity' => '500',
                'metric_unit' => 'g',
            ])
            ->assertSessionHasErrors([
                'name' => 'Surovina s tímto názvem už v aktuální rodině existuje.',
            ]);

        $this->assertDatabaseHas('ingredients', [
            'id' => $ingredient->id,
            'name' => 'Mouka',
        ]);
        $this->assertDatabaseHas('ingredients', [
            'id' => $foreignIngredient->id,
            'name' => 'Olej',
        ]);
    }

    public function test_all_approved_metric_input_units_normalize_before_persistence(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $examples = [
            ['Miligramy', '500000', 'mg', '500', null],
            ['Gramy', '750', 'g', '750', null],
            ['Kilogramy', '1.25', 'kg', '1250', null],
            ['Mililitry', '500', 'ml', null, '500'],
            ['Centilitry', '25', 'cl', null, '250'],
            ['Litry', '1.5', 'l', null, '1500'],
        ];

        foreach ($examples as [$name, $quantity, $unit, $weightGrams, $volumeMillilitres]) {
            $this
                ->actingAs($user)
                ->post(route('ingredients.store'), [
                    'name' => $name,
                    'metric_quantity' => $quantity,
                    'metric_unit' => $unit,
                ])
                ->assertSessionHasNoErrors();

            $this->assertDatabaseHas('ingredients', [
                'family_id' => $family->id,
                'name' => $name,
                'weight_grams' => $weightGrams,
                'volume_millilitres' => $volumeMillilitres,
            ]);
        }
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
                'metric_quantity' => '500',
                'metric_unit' => 'g',
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
                'metric_quantity' => '500',
                'metric_unit' => 'g',
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
                'metric_quantity' => '500',
                'metric_unit' => 'g',
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
            'metric_quantity' => '500',
            'metric_unit' => 'g',
        ])->assertSessionHasErrors('name');
        $this->actingAs($user)->post(route('ingredients.store'), [
            'name' => str_repeat('a', 256),
            'metric_quantity' => '500',
            'metric_unit' => 'g',
        ])->assertSessionHasErrors('name');
        $this->actingAs($user)->post(route('ingredients.store'), [
            'name' => ['Rýže'],
            'metric_quantity' => '500',
            'metric_unit' => 'g',
        ])->assertSessionHasErrors('name');
        $this->actingAs($user)->post(route('ingredients.store'), [
            'name' => 'Rýže',
        ])->assertSessionHasErrors([
            'quantities' => 'Zadejte metrické množství nebo počet kusů v balení.',
        ]);
        $this->actingAs($user)->post(route('ingredients.store'), [
            'name' => 'Rýže',
            'metric_quantity' => '500',
        ])->assertSessionHasErrors([
            'metric_unit' => 'Vyberte jednotku metrického množství.',
        ]);

        foreach (['0', '-1', '1.1234567', '100000000000000', ['500']] as $invalidQuantity) {
            $this
                ->actingAs($user)
                ->post(route('ingredients.store'), [
                    'name' => 'Rýže',
                    'metric_quantity' => $invalidQuantity,
                    'metric_unit' => 'g',
                ])
                ->assertSessionHasErrors('metric_quantity');
        }

        $this->actingAs($user)->post(route('ingredients.store'), [
            'name' => 'Rýže',
            'metric_quantity' => '500',
            'metric_unit' => 'oz',
        ])->assertSessionHasErrors('metric_unit');
        $this->actingAs($user)->post(route('ingredients.store'), [
            'name' => 'Rýže',
            'metric_quantity' => '0.000001',
            'metric_unit' => 'mg',
        ])->assertSessionHasErrors([
            'metric_quantity' => 'Množství po převodu musí mít nejvýše šest desetinných míst a vejít se do podporovaného rozsahu.',
        ]);
        $this->actingAs($user)->post(route('ingredients.store'), [
            'name' => 'Rýže',
            'piece_count' => '1',
            'description' => ['Popis'],
        ])->assertSessionHasErrors('description');

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

    public function test_application_rejects_invalid_package_quantity_combinations(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);

        $this->actingAs($user)->post(route('ingredients.store'), [
            'name' => 'Bez množství',
        ])->assertSessionHasErrors('quantities');
        $this->actingAs($user)->post(route('ingredients.store'), [
            'name' => 'Nulová míra',
            'metric_quantity' => '0',
            'metric_unit' => 'g',
        ])->assertSessionHasErrors('metric_quantity');
        $this->actingAs($user)->post(route('ingredients.store'), [
            'name' => 'Nulový počet kusů',
            'piece_count' => '0',
        ])->assertSessionHasErrors('piece_count');

        $this->assertDatabaseCount('ingredients', 0);
    }

    public function test_ingredient_management_requires_a_current_family(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('ingredients.index'))->assertNotFound();
        $this->actingAs($user)->post(route('ingredients.store'), [
            'name' => 'Rýže',
            'metric_quantity' => '500',
            'metric_unit' => 'g',
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
