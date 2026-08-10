<?php

declare(strict_types=1);

namespace Tests\Feature\Cookbook;

use App\Cookbook\Models\StoreSection;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class StoreSectionManagementTest extends TestCase
{
    public function test_guests_cannot_create_store_sections(): void
    {
        $this
            ->post(route('store-sections.store'), [
                'name' => 'Zelenina',
                'colour' => '#2F855A',
            ])
            ->assertRedirect(route('login'));
    }

    public function test_each_member_can_create_and_list_store_sections_in_their_shared_family(): void
    {
        $firstMember = User::factory()->create();
        $secondMember = User::factory()->create();
        $family = $this->createFamilyWithMembers($firstMember, $secondMember);
        $this->selectCurrentFamily($firstMember, $family);
        $this->selectCurrentFamily($secondMember, $family);

        $this
            ->actingAs($firstMember)
            ->post(route('store-sections.store'), [
                'name' => '  Čerstvá   zelenina  ',
                'colour' => '#2F855A',
            ])
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Část obchodu byla vytvořena.',
            ])
            ->assertRedirect(route('stores.index'));

        $this->assertDatabaseHas('store_sections', [
            'family_id' => $family->id,
            'name' => 'Čerstvá zelenina',
            'normalized_name' => 'čerstvá zelenina',
            'colour' => '#2F855A',
        ]);

        $this
            ->actingAs($secondMember)
            ->post(route('store-sections.store'), [
                'name' => 'Pečivo',
                'colour' => '#D97706',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('stores.index'));

        $this->assertDatabaseHas('store_sections', [
            'family_id' => $family->id,
            'name' => 'Pečivo',
            'colour' => '#D97706',
        ]);

        $this
            ->actingAs($secondMember)
            ->get(route('stores.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->component('stores/Index')
                ->has('storeSections', 2)
                ->where('storeSections.0.name', 'Pečivo')
                ->where('storeSections.0.colour', '#D97706')
                ->where('storeSections.1.name', 'Čerstvá zelenina')
                ->where('storeSections.1.colour', '#2F855A'));
    }

    public function test_store_section_reads_and_writes_are_scoped_to_the_current_family(): void
    {
        $user = User::factory()->create();
        $currentFamily = $this->createFamilyWithMembers($user);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($user, $currentFamily);
        $visibleSection = StoreSection::factory()->for($currentFamily)->create([
            'name' => 'Viditelná část',
            'colour' => '#2563EB',
        ]);
        StoreSection::factory()->for($otherFamily)->create([
            'name' => 'Soukromá část',
            'colour' => '#DC2626',
        ]);

        $this
            ->actingAs($user)
            ->post(route('store-sections.store'), [
                'name' => 'Nová část',
                'colour' => '#16A34A',
                'family_id' => $otherFamily->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('store_sections', [
            'family_id' => $currentFamily->id,
            'name' => 'Nová část',
        ]);
        $this->assertDatabaseMissing('store_sections', [
            'family_id' => $otherFamily->id,
            'name' => 'Nová část',
        ]);

        $this
            ->actingAs($user)
            ->get(route('stores.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('storeSections', 2)
                ->where('storeSections.0.name', 'Nová část')
                ->where('storeSections.1.id', $visibleSection->id)
                ->where('storeSections.1.name', 'Viditelná část'));
    }

    public function test_normalized_store_section_name_is_unique_within_a_family(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        StoreSection::factory()->for($family)->create(['name' => 'Čerstvá zelenina']);

        $this
            ->actingAs($user)
            ->post(route('store-sections.store'), [
                'name' => '  ČERSTVÁ   zelenina ',
                'colour' => '#0F766E',
            ])
            ->assertSessionHasErrors([
                'name' => 'Část obchodu s tímto názvem už v aktuální rodině existuje.',
            ]);

        $this->assertDatabaseCount('store_sections', 1);
    }

    public function test_same_normalized_store_section_name_is_allowed_in_another_family(): void
    {
        $user = User::factory()->create();
        $currentFamily = $this->createFamilyWithMembers($user);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($user, $currentFamily);
        StoreSection::factory()->for($otherFamily)->create(['name' => 'Zelenina']);

        $this
            ->actingAs($user)
            ->post(route('store-sections.store'), [
                'name' => 'ZELENINA',
                'colour' => '#65A30D',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('store_sections', [
            'family_id' => $currentFamily->id,
            'normalized_name' => 'zelenina',
        ]);
    }

    public function test_store_section_name_and_colour_are_validated(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);

        $this
            ->actingAs($user)
            ->post(route('store-sections.store'), ['name' => '   ', 'colour' => '#123456'])
            ->assertSessionHasErrors('name');
        $this
            ->actingAs($user)
            ->post(route('store-sections.store'), ['name' => str_repeat('a', 256), 'colour' => '#123456'])
            ->assertSessionHasErrors('name');
        $this
            ->actingAs($user)
            ->post(route('store-sections.store'), ['name' => 'Zelenina'])
            ->assertSessionHasErrors('colour');

        foreach (['123456', '#12345', '#1234567', '#GGGGGG'] as $invalidColour) {
            $this
                ->actingAs($user)
                ->post(route('store-sections.store'), [
                    'name' => 'Zelenina',
                    'colour' => $invalidColour,
                ])
                ->assertSessionHasErrors([
                    'colour' => 'Barva musí být šestimístný hexadecimální kód.',
                ]);
        }

        $this->assertDatabaseCount('store_sections', 0);
    }

    public function test_store_section_creation_requires_a_current_family(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('store-sections.store'), [
                'name' => 'Zelenina',
                'colour' => '#2F855A',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('store_sections', 0);
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
