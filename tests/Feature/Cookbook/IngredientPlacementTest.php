<?php

declare(strict_types=1);

namespace Tests\Feature\Cookbook;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\Store;
use App\Cookbook\Models\StoreSection;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class IngredientPlacementTest extends TestCase
{
    public function test_a_member_can_assign_and_clear_a_valid_current_family_store_placement(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $store = Store::factory()->for($family)->create();
        $section = StoreSection::factory()->for($family)->create();
        $store->storeSections()->attach($section, ['position' => 0]);
        $ingredient = Ingredient::factory()->for($family)->create();

        $this->actingAs($user)->patch(route('ingredients.update', $ingredient), [
            'name' => $ingredient->name,
            'metric_quantity' => '500',
            'metric_unit' => 'g',
            'store_id' => (string) $store->id,
            'store_section_id' => (string) $section->id,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ingredients', [
            'id' => $ingredient->id,
            'store_id' => $store->id,
            'store_section_id' => $section->id,
        ]);

        $this->actingAs($user)->get(route('ingredients.index'))->assertInertia(
            fn (AssertableInertia $page): AssertableInertia => $page
                ->where('ingredients.0.placement', "{$store->name} · {$section->name}")
                ->where('ingredients.0.storeId', $store->id)
                ->where('ingredients.0.storeSectionId', $section->id)
                ->where('ingredients.0.store.id', $store->id)
                ->where('ingredients.0.storeSection.id', $section->id)
                ->missing('stores'),
        );

        $this->actingAs($user)->patch(route('ingredients.update', $ingredient), [
            'name' => $ingredient->name,
            'metric_quantity' => '500',
            'metric_unit' => 'g',
            'store_id' => '',
            'store_section_id' => '',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ingredients', [
            'id' => $ingredient->id,
            'store_id' => null,
            'store_section_id' => null,
        ]);
    }

    public function test_placement_rejects_a_section_without_its_store_or_records_outside_current_family(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $otherFamily = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $store = Store::factory()->for($family)->create();
        $unassociatedSection = StoreSection::factory()->for($family)->create();
        $otherStore = Store::factory()->for($otherFamily)->create();
        $otherSection = StoreSection::factory()->for($otherFamily)->create();
        $otherStore->storeSections()->attach($otherSection, ['position' => 0]);
        $ingredient = Ingredient::factory()->for($family)->create();

        foreach ([
            ['', (string) $unassociatedSection->id, 'store_id'],
            [(string) $store->id, (string) $unassociatedSection->id, 'store_section_id'],
            [(string) $otherStore->id, '', 'store_id'],
            [(string) $otherStore->id, (string) $otherSection->id, 'store_id'],
        ] as [$storeId, $sectionId, $errorField]) {
            $this->actingAs($user)->patch(route('ingredients.update', $ingredient), [
                'name' => $ingredient->name,
                'metric_quantity' => '500',
                'metric_unit' => 'g',
                'store_id' => $storeId,
                'store_section_id' => $sectionId,
                'family_id' => $otherFamily->id,
            ])->assertSessionHasErrors($errorField);
        }

        $this->assertDatabaseHas('ingredients', [
            'id' => $ingredient->id,
            'store_id' => null,
            'store_section_id' => null,
        ]);
    }

    public function test_the_database_rejects_a_store_section_pair_without_an_association(): void
    {
        $family = $this->createFamilyWithMembers();
        $store = Store::factory()->for($family)->create();
        $section = StoreSection::factory()->for($family)->create();

        $this->expectException(QueryException::class);

        Ingredient::factory()->for($family)->create([
            'store_id' => $store->id,
            'store_section_id' => $section->id,
        ]);
    }

    public function test_the_application_rejects_a_store_section_without_a_store(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $section = StoreSection::factory()->for($family)->create();

        $this->actingAs($user)->post(route('ingredients.store'), [
            'name' => 'Rýže',
            'metric_quantity' => '500',
            'metric_unit' => 'g',
            'store_section_id' => $section->id,
        ])->assertSessionHasErrors([
            'store_id' => 'Před výběrem části obchodu vyberte obchod.',
        ]);

        $this->assertDatabaseCount('ingredients', 0);
    }

    public function test_a_detach_race_becomes_a_store_section_field_error_without_a_partial_update(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $store = Store::factory()->for($family)->create();
        $section = StoreSection::factory()->for($family)->create();
        $store->storeSections()->attach($section, ['position' => 0]);
        $ingredient = Ingredient::factory()->for($family)->create(['name' => 'Původní název']);
        $eventName = 'eloquent.saving: ' . Ingredient::class;

        Event::listen($eventName, function (Ingredient $savingIngredient) use ($ingredient, $store, $section): void {
            if ($savingIngredient->is($ingredient)) {
                DB::table('store_store_section')
                    ->where('store_id', $store->id)
                    ->where('store_section_id', $section->id)
                    ->delete();
            }
        });

        try {
            $this->actingAs($user)->patch(route('ingredients.update', $ingredient), [
                'name' => 'Nový název',
                'metric_quantity' => '500',
                'metric_unit' => 'g',
                'store_id' => (string) $store->id,
                'store_section_id' => (string) $section->id,
            ])->assertSessionHasErrors([
                'store_section_id' => 'Vybraná část obchodu už není k tomuto obchodu přiřazená.',
            ]);
        } finally {
            Event::forget($eventName);
        }

        $this->assertDatabaseHas('ingredients', [
            'id' => $ingredient->id,
            'name' => 'Původní název',
            'store_id' => null,
            'store_section_id' => null,
        ]);
        $this->assertDatabaseHas('store_store_section', [
            'store_id' => $store->id,
            'store_section_id' => $section->id,
        ]);
    }

    public function test_store_and_section_lifecycles_clear_only_the_required_placement_fields(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $firstStore = Store::factory()->for($family)->create();
        $secondStore = Store::factory()->for($family)->create();
        $section = StoreSection::factory()->for($family)->create();
        $firstStore->storeSections()->attach($section, ['position' => 0]);
        $secondStore->storeSections()->attach($section, ['position' => 0]);
        $detachedPlacement = Ingredient::factory()->for($family)->create([
            'store_id' => $firstStore->id,
            'store_section_id' => $section->id,
        ]);
        $deletedSectionPlacement = Ingredient::factory()->for($family)->create([
            'store_id' => $secondStore->id,
            'store_section_id' => $section->id,
        ]);

        $this->actingAs($user)->delete(route('stores.store-sections.destroy', [$firstStore, $section]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('ingredients', [
            'id' => $detachedPlacement->id,
            'store_id' => $firstStore->id,
            'store_section_id' => null,
        ]);
        $this->assertDatabaseHas('ingredients', [
            'id' => $deletedSectionPlacement->id,
            'store_id' => $secondStore->id,
            'store_section_id' => $section->id,
        ]);

        $this->actingAs($user)->delete(route('store-sections.destroy', $section))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('ingredients', [
            'id' => $deletedSectionPlacement->id,
            'store_id' => $secondStore->id,
            'store_section_id' => null,
        ]);

        $this->actingAs($user)->delete(route('stores.destroy', $firstStore))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('ingredients', [
            'id' => $detachedPlacement->id,
            'store_id' => null,
            'store_section_id' => null,
        ]);
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
