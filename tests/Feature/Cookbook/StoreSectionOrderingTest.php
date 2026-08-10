<?php

declare(strict_types=1);

namespace Tests\Feature\Cookbook;

use App\Cookbook\Models\Store;
use App\Cookbook\Models\StoreSection;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class StoreSectionOrderingTest extends TestCase
{
    public function test_guests_cannot_change_store_section_associations(): void
    {
        $this
            ->post(route('stores.store-sections.store', 1), ['store_section_id' => 1])
            ->assertRedirect(route('login'));
        $this
            ->delete(route('stores.store-sections.destroy', [1, 1]))
            ->assertRedirect(route('login'));
        $this
            ->put(route('stores.store-sections.update', 1), [
                'store_section_ids' => [1],
                'version' => 0,
            ])
            ->assertRedirect(route('login'));
    }

    public function test_each_member_can_associate_and_list_a_store_section_in_traversal_order(): void
    {
        $firstMember = User::factory()->create();
        $secondMember = User::factory()->create();
        $family = $this->createFamilyWithMembers($firstMember, $secondMember);
        $this->selectCurrentFamily($firstMember, $family);
        $this->selectCurrentFamily($secondMember, $family);
        $store = Store::factory()->for($family)->create(['name' => 'Farmářský trh']);
        $storeSection = StoreSection::factory()->for($family)->create([
            'name' => 'Zelenina',
            'colour' => '#2F855A',
        ]);

        $this
            ->actingAs($firstMember)
            ->post(route('stores.store-sections.store', $store), [
                'store_section_id' => $storeSection->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Část obchodu byla přiřazena.',
            ])
            ->assertRedirect(route('stores.index'));

        $this->assertDatabaseHas('store_store_section', [
            'store_id' => $store->id,
            'store_section_id' => $storeSection->id,
            'position' => 0,
        ]);

        $this
            ->actingAs($secondMember)
            ->get(route('stores.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->component('stores/Index')
                ->has('stores', 1)
                ->where('stores.0.id', $store->id)
                ->where('stores.0.sectionOrderVersion', 1)
                ->has('stores.0.sections', 1)
                ->where('stores.0.sections.0.id', $storeSection->id)
                ->where('stores.0.sections.0.name', 'Zelenina')
                ->where('stores.0.sections.0.colour', '#2F855A')
                ->where('stores.0.sections.0.position', 0));
    }

    public function test_association_resolves_both_records_inside_the_current_family(): void
    {
        $user = User::factory()->create();
        $currentFamily = $this->createFamilyWithMembers($user);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($user, $currentFamily);
        $currentStore = Store::factory()->for($currentFamily)->create();
        $otherStore = Store::factory()->for($otherFamily)->create();
        $currentSection = StoreSection::factory()->for($currentFamily)->create();
        $otherSection = StoreSection::factory()->for($otherFamily)->create();

        $this
            ->actingAs($user)
            ->post(route('stores.store-sections.store', $currentStore), [
                'store_section_id' => $otherSection->id,
            ])
            ->assertNotFound();
        $this
            ->actingAs($user)
            ->post(route('stores.store-sections.store', $otherStore), [
                'store_section_id' => $currentSection->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('store_store_section', 0);

        $this
            ->actingAs($user)
            ->post(route('stores.store-sections.store', $currentStore), [
                'store_section_id' => $currentSection->id,
                'family_id' => $otherFamily->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('store_store_section', [
            'store_id' => $currentStore->id,
            'store_section_id' => $currentSection->id,
        ]);
        $this->assertDatabaseMissing('store_store_section', [
            'store_id' => $otherStore->id,
            'store_section_id' => $currentSection->id,
        ]);
    }

    public function test_association_input_and_duplicate_assignment_are_validated(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $store = Store::factory()->for($family)->create(['section_order_version' => 1]);
        $section = StoreSection::factory()->for($family)->create();
        $store->storeSections()->attach($section, ['position' => 0]);

        foreach ([[], ['store_section_id' => ['invalid']]] as $invalidPayload) {
            $this
                ->actingAs($user)
                ->post(route('stores.store-sections.store', $store), $invalidPayload)
                ->assertSessionHasErrors('store_section_id');
        }

        $this
            ->actingAs($user)
            ->post(route('stores.store-sections.store', $store), [
                'store_section_id' => $section->id,
            ])
            ->assertSessionHasErrors([
                'store_section_id' => 'Tato část obchodu už je k obchodu přiřazena.',
            ]);

        $this->assertDatabaseCount('store_store_section', 1);
        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'section_order_version' => 1,
        ]);
    }

    public function test_a_member_can_remove_an_association_and_positions_remain_contiguous(): void
    {
        $firstMember = User::factory()->create();
        $secondMember = User::factory()->create();
        $family = $this->createFamilyWithMembers($firstMember, $secondMember);
        $this->selectCurrentFamily($secondMember, $family);
        $store = Store::factory()->for($family)->create(['section_order_version' => 3]);
        $sections = StoreSection::factory()->for($family)->count(3)->create();
        $store->storeSections()->attach([
            $sections[0]->id => ['position' => 0],
            $sections[1]->id => ['position' => 1],
            $sections[2]->id => ['position' => 2],
        ]);

        $this
            ->actingAs($secondMember)
            ->delete(route('stores.store-sections.destroy', [$store, $sections[1]]))
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Část obchodu byla odebrána.',
            ])
            ->assertRedirect(route('stores.index'));

        $this->assertDatabaseMissing('store_store_section', [
            'store_id' => $store->id,
            'store_section_id' => $sections[1]->id,
        ]);
        $this->assertModelExists($sections[1]);
        $this->assertDatabaseHas('store_store_section', [
            'store_id' => $store->id,
            'store_section_id' => $sections[0]->id,
            'position' => 0,
        ]);
        $this->assertDatabaseHas('store_store_section', [
            'store_id' => $store->id,
            'store_section_id' => $sections[2]->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'section_order_version' => 4,
        ]);

        $this
            ->actingAs($secondMember)
            ->get(route('stores.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('stores.0.sectionOrderVersion', 4)
                ->has('stores.0.sections', 2)
                ->where('stores.0.sections.0.id', $sections[0]->id)
                ->where('stores.0.sections.0.position', 0)
                ->where('stores.0.sections.1.id', $sections[2]->id)
                ->where('stores.0.sections.1.position', 1));
    }

    public function test_a_member_can_rewrite_the_complete_contiguous_section_order(): void
    {
        $firstMember = User::factory()->create();
        $secondMember = User::factory()->create();
        $family = $this->createFamilyWithMembers($firstMember, $secondMember);
        $this->selectCurrentFamily($secondMember, $family);
        $store = Store::factory()->for($family)->create(['section_order_version' => 3]);
        $sections = StoreSection::factory()->for($family)->count(3)->create();
        $store->storeSections()->attach([
            $sections[0]->id => ['position' => 0],
            $sections[1]->id => ['position' => 1],
            $sections[2]->id => ['position' => 2],
        ]);

        $this
            ->actingAs($secondMember)
            ->put(route('stores.store-sections.update', $store), [
                'store_section_ids' => [
                    $sections[2]->id,
                    $sections[0]->id,
                    $sections[1]->id,
                ],
                'version' => 3,
            ])
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Pořadí částí obchodu bylo uloženo.',
            ])
            ->assertRedirect(route('stores.index'));

        foreach ([$sections[2], $sections[0], $sections[1]] as $position => $section) {
            $this->assertDatabaseHas('store_store_section', [
                'store_id' => $store->id,
                'store_section_id' => $section->id,
                'position' => $position,
            ]);
        }

        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'section_order_version' => 4,
        ]);

        $this
            ->actingAs($secondMember)
            ->get(route('stores.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('stores.0.sectionOrderVersion', 4)
                ->where('stores.0.sections.0.id', $sections[2]->id)
                ->where('stores.0.sections.0.position', 0)
                ->where('stores.0.sections.1.id', $sections[0]->id)
                ->where('stores.0.sections.1.position', 1)
                ->where('stores.0.sections.2.id', $sections[1]->id)
                ->where('stores.0.sections.2.position', 2));
    }

    public function test_a_stale_reorder_is_rejected_with_the_fresh_order_unchanged(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $store = Store::factory()->for($family)->create(['section_order_version' => 4]);
        $sections = StoreSection::factory()->for($family)->count(2)->create();
        $store->storeSections()->attach([
            $sections[0]->id => ['position' => 0],
            $sections[1]->id => ['position' => 1],
        ]);

        $this
            ->actingAs($user)
            ->put(route('stores.store-sections.update', $store), [
                'store_section_ids' => [$sections[1]->id, $sections[0]->id],
                'version' => 3,
            ])
            ->assertSessionHasErrors([
                'version' => 'Pořadí částí obchodu se mezitím změnilo. Zkontrolujte nové pořadí a zkuste to znovu.',
            ]);

        $this->assertDatabaseHas('store_store_section', [
            'store_id' => $store->id,
            'store_section_id' => $sections[0]->id,
            'position' => 0,
        ]);
        $this->assertDatabaseHas('store_store_section', [
            'store_id' => $store->id,
            'store_section_id' => $sections[1]->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'section_order_version' => 4,
        ]);
    }

    public function test_reorder_requires_each_current_association_exactly_once(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($user, $family);
        $store = Store::factory()->for($family)->create(['section_order_version' => 2]);
        $sections = StoreSection::factory()->for($family)->count(2)->create();
        $foreignSection = StoreSection::factory()->for($otherFamily)->create();
        $store->storeSections()->attach([
            $sections[0]->id => ['position' => 0],
            $sections[1]->id => ['position' => 1],
        ]);

        foreach ([
            [$sections[0]->id],
            [$sections[0]->id, $sections[0]->id],
            [$sections[0]->id, $foreignSection->id],
        ] as $invalidOrder) {
            $this
                ->actingAs($user)
                ->put(route('stores.store-sections.update', $store), [
                    'store_section_ids' => $invalidOrder,
                    'version' => 2,
                ])
                ->assertSessionHasErrors('store_section_ids');
        }

        $this->assertDatabaseHas('store_store_section', [
            'store_id' => $store->id,
            'store_section_id' => $sections[0]->id,
            'position' => 0,
        ]);
        $this->assertDatabaseHas('store_store_section', [
            'store_id' => $store->id,
            'store_section_id' => $sections[1]->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'section_order_version' => 2,
        ]);
    }

    public function test_removal_and_reorder_cannot_target_another_family(): void
    {
        $user = User::factory()->create();
        $currentFamily = $this->createFamilyWithMembers($user);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($user, $currentFamily);
        $otherStore = Store::factory()->for($otherFamily)->create(['section_order_version' => 1]);
        $otherSection = StoreSection::factory()->for($otherFamily)->create();
        $otherStore->storeSections()->attach($otherSection, ['position' => 0]);

        $this
            ->actingAs($user)
            ->delete(route('stores.store-sections.destroy', [$otherStore, $otherSection]))
            ->assertNotFound();
        $this
            ->actingAs($user)
            ->put(route('stores.store-sections.update', $otherStore), [
                'store_section_ids' => [$otherSection->id],
                'version' => 1,
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('store_store_section', [
            'store_id' => $otherStore->id,
            'store_section_id' => $otherSection->id,
            'position' => 0,
        ]);
        $this->assertDatabaseHas('stores', [
            'id' => $otherStore->id,
            'section_order_version' => 1,
        ]);
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
