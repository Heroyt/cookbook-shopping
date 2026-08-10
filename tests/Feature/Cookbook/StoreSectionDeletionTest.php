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

final class StoreSectionDeletionTest extends TestCase
{
    public function test_guests_cannot_delete_store_sections(): void
    {
        $storeSection = StoreSection::factory()->create();

        $this
            ->delete(route('store-sections.destroy', $storeSection))
            ->assertRedirect(route('login'));

        $this->assertModelExists($storeSection);
    }

    public function test_each_member_can_delete_a_store_section_and_reuse_its_name(): void
    {
        $firstMember = User::factory()->create();
        $secondMember = User::factory()->create();
        $family = $this->createFamilyWithMembers($firstMember, $secondMember);
        $this->selectCurrentFamily($firstMember, $family);
        $this->selectCurrentFamily($secondMember, $family);
        $storeSection = StoreSection::factory()->for($family)->create([
            'name' => 'Čerstvá zelenina',
        ]);

        $this
            ->actingAs($secondMember)
            ->delete(route('store-sections.destroy', $storeSection))
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Část obchodu byla smazána.',
            ])
            ->assertRedirect(route('stores.index'));

        $this->assertModelMissing($storeSection);

        $this
            ->actingAs($firstMember)
            ->post(route('store-sections.store'), [
                'name' => '  ČERSTVÁ   zelenina  ',
                'colour' => '#2F855A',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('store_sections', [
            'family_id' => $family->id,
            'name' => 'ČERSTVÁ zelenina',
            'normalized_name' => 'čerstvá zelenina',
        ]);
    }

    public function test_deletion_removes_associations_and_reorders_each_affected_store(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $firstStore = Store::factory()->for($family)->create(['section_order_version' => 3]);
        $secondStore = Store::factory()->for($family)->create(['section_order_version' => 7]);
        $unaffectedStore = Store::factory()->for($family)->create(['section_order_version' => 9]);
        $deletedSection = StoreSection::factory()->for($family)->create();
        $firstRemainingSection = StoreSection::factory()->for($family)->create();
        $secondRemainingSection = StoreSection::factory()->for($family)->create();

        $firstStore->storeSections()->attach([
            $deletedSection->id => ['position' => 0],
            $firstRemainingSection->id => ['position' => 1],
            $secondRemainingSection->id => ['position' => 2],
        ]);
        $secondStore->storeSections()->attach([
            $secondRemainingSection->id => ['position' => 0],
            $deletedSection->id => ['position' => 1],
        ]);
        $unaffectedStore->storeSections()->attach($firstRemainingSection, ['position' => 0]);

        $this
            ->actingAs($user)
            ->delete(route('store-sections.destroy', $deletedSection))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('stores.index'));

        $this->assertDatabaseMissing('store_store_section', [
            'store_section_id' => $deletedSection->id,
        ]);
        $this->assertDatabaseHas('store_store_section', [
            'store_id' => $firstStore->id,
            'store_section_id' => $firstRemainingSection->id,
            'position' => 0,
        ]);
        $this->assertDatabaseHas('store_store_section', [
            'store_id' => $firstStore->id,
            'store_section_id' => $secondRemainingSection->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('store_store_section', [
            'store_id' => $secondStore->id,
            'store_section_id' => $secondRemainingSection->id,
            'position' => 0,
        ]);
        $this->assertSame(4, $firstStore->fresh()->section_order_version);
        $this->assertSame(8, $secondStore->fresh()->section_order_version);
        $this->assertSame(9, $unaffectedStore->fresh()->section_order_version);
    }

    public function test_store_section_list_discloses_its_store_association_count(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $otherFamily = $this->createFamilyWithMembers();
        $this->selectCurrentFamily($user, $family);
        $storeSection = StoreSection::factory()->for($family)->create();
        $firstStore = Store::factory()->for($family)->create();
        $secondStore = Store::factory()->for($family)->create();
        $otherFamilyStore = Store::factory()->for($otherFamily)->create();
        $firstStore->storeSections()->attach($storeSection, ['position' => 0]);
        $secondStore->storeSections()->attach($storeSection, ['position' => 0]);
        $otherFamilyStore->storeSections()->attach($storeSection, ['position' => 0]);

        $this
            ->actingAs($user)
            ->get(route('stores.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('storeSections.0.id', $storeSection->id)
                ->where('storeSections.0.associationCount', 2));
    }

    public function test_deletion_uses_only_the_authenticated_users_current_family(): void
    {
        $user = User::factory()->create();
        $currentFamily = $this->createFamilyWithMembers($user);
        $otherFamily = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $currentFamily);
        $otherSection = StoreSection::factory()->for($otherFamily)->create();

        $this
            ->actingAs($user)
            ->delete(route('store-sections.destroy', $otherSection), [
                'family_id' => $otherFamily->id,
            ])
            ->assertNotFound();

        $this->assertModelExists($otherSection);
    }

    public function test_store_section_deletion_requires_a_current_family(): void
    {
        $user = User::factory()->create();
        $storeSection = StoreSection::factory()->create();

        $this
            ->actingAs($user)
            ->delete(route('store-sections.destroy', $storeSection))
            ->assertNotFound();

        $this->assertModelExists($storeSection);
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
