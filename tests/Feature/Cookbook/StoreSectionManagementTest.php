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
                'icon' => 'apple',
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
            'icon' => 'apple',
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
                ->where('storeSections.0.icon', 'package')
                ->where('storeSections.1.name', 'Čerstvá zelenina')
                ->where('storeSections.1.colour', '#2F855A')
                ->where('storeSections.1.icon', 'apple'));
    }

    public function test_layered_creation_associates_the_section_and_returns_it_to_the_parent_form(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $store = Store::factory()->for($family)->create();

        $this->actingAs($user)
            ->from(route('ingredients.index'))
            ->post(route('store-sections.store'), [
                'name' => 'Pečivo',
                'colour' => '#D97706',
                'icon' => 'package',
                'store_id' => $store->id,
                'layered' => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('ingredients.index'))
            ->assertInertiaFlash('createdStoreSection', [
                'id' => StoreSection::query()->sole()->id,
                'name' => 'Pečivo',
                'colour' => '#D97706',
                'icon' => 'package',
                'iconUrl' => null,
            ]);

        $this->assertDatabaseHas('store_store_section', [
            'store_id' => $store->id,
            'store_section_id' => StoreSection::query()->sole()->id,
            'position' => 0,
        ]);
    }

    public function test_each_member_can_change_a_store_section_icon_within_the_current_family(): void
    {
        $firstMember = User::factory()->create();
        $secondMember = User::factory()->create();
        $family = $this->createFamilyWithMembers($firstMember, $secondMember);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($firstMember, $family);
        $this->selectCurrentFamily($secondMember, $family);
        $section = StoreSection::factory()->for($family)->create();
        $foreignSection = StoreSection::factory()->for($otherFamily)->create();

        $this
            ->actingAs($secondMember)
            ->patch(route('store-sections.icon.update', $section), ['icon' => 'carrot'])
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Ikona části obchodu byla změněna.',
            ])
            ->assertRedirect(route('stores.index'));

        $this->assertDatabaseHas('store_sections', [
            'id' => $section->id,
            'icon' => 'carrot',
        ]);

        $this
            ->actingAs($firstMember)
            ->patch(route('store-sections.icon.update', $foreignSection), ['icon' => 'fish'])
            ->assertNotFound();
    }

    public function test_each_member_can_update_a_store_section_within_the_current_family(): void
    {
        $firstMember = User::factory()->create();
        $secondMember = User::factory()->create();
        $family = $this->createFamilyWithMembers($firstMember, $secondMember);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($firstMember, $family);
        $this->selectCurrentFamily($secondMember, $family);
        $section = StoreSection::factory()->for($family)->create([
            'name' => 'Původní část',
            'colour' => '#2F855A',
            'icon' => 'package',
        ]);
        $foreignSection = StoreSection::factory()->for($otherFamily)->create();

        $this
            ->actingAs($secondMember)
            ->patch(route('store-sections.update', $section), [
                'name' => '  Mléko   a sýry ',
                'colour' => '#2563EB',
                'icon' => 'milk',
            ])
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Část obchodu byla upravena.',
            ])
            ->assertRedirect(route('stores.index'));

        $this->assertDatabaseHas('store_sections', [
            'id' => $section->id,
            'family_id' => $family->id,
            'name' => 'Mléko a sýry',
            'normalized_name' => 'mléko a sýry',
            'colour' => '#2563EB',
            'icon' => 'milk',
        ]);

        $this
            ->actingAs($firstMember)
            ->patch(route('store-sections.update', $foreignSection), [
                'name' => 'Cizí část',
                'colour' => '#DC2626',
                'icon' => 'fish',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('store_sections', [
            'id' => $foreignSection->id,
            'name' => 'Cizí část',
        ]);
    }

    public function test_each_member_can_change_only_a_store_section_colour_from_ingredient_edit(): void
    {
        $firstMember = User::factory()->create();
        $secondMember = User::factory()->create();
        $family = $this->createFamilyWithMembers($firstMember, $secondMember);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($firstMember, $family);
        $this->selectCurrentFamily($secondMember, $family);
        $section = StoreSection::factory()->for($family)->create([
            'name' => 'Mléčné výrobky',
            'colour' => '#2F855A',
            'icon' => 'milk',
        ]);
        $foreignSection = StoreSection::factory()->for($otherFamily)->create();

        $this
            ->actingAs($secondMember)
            ->patch(route('store-sections.colour.update', $section), [
                'colour' => '#7C3AED',
            ])
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Barva části obchodu byla změněna.',
            ]);

        $this->assertDatabaseHas('store_sections', [
            'id' => $section->id,
            'name' => 'Mléčné výrobky',
            'colour' => '#7C3AED',
            'icon' => 'milk',
        ]);

        $this
            ->actingAs($firstMember)
            ->patch(route('store-sections.colour.update', $foreignSection), [
                'colour' => '#DC2626',
            ])
            ->assertNotFound();
    }

    public function test_store_section_icon_changes_require_authentication_and_an_allowlisted_icon(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $section = StoreSection::factory()->for($family)->create();

        $this
            ->patch(route('store-sections.icon.update', $section), ['icon' => 'carrot'])
            ->assertRedirect(route('login'));

        $this
            ->actingAs($user)
            ->patch(route('store-sections.icon.update', $section), ['icon' => 'uploaded-file.svg'])
            ->assertSessionHasErrors('icon');

        $this->assertDatabaseHas('store_sections', [
            'id' => $section->id,
            'icon' => 'package',
        ]);
    }

    public function test_store_section_updates_require_complete_valid_fields(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $section = StoreSection::factory()->for($family)->create([
            'name' => 'Pečivo',
            'colour' => '#D97706',
            'icon' => 'croissant',
        ]);

        $this
            ->actingAs($user)
            ->patch(route('store-sections.update', $section), [
                'name' => 'Pekařství',
                'colour' => '#7C3AED',
            ])
            ->assertSessionHasErrors('icon');

        $this
            ->actingAs($user)
            ->patch(route('store-sections.colour.update', $section), [
                'colour' => '7C3AED',
            ])
            ->assertSessionHasErrors([
                'colour' => 'Barva musí být šestimístný hexadecimální kód.',
            ]);

        $this->assertDatabaseHas('store_sections', [
            'id' => $section->id,
            'name' => 'Pečivo',
            'colour' => '#D97706',
            'icon' => 'croissant',
        ]);
    }

    public function test_extended_grocery_icon_catalogue_is_allowlisted(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $section = StoreSection::factory()->for($family)->create();

        foreach ([
            'banana',
            'egg',
            'nut',
            'wheat',
            'soup',
            'cake-slice',
            'cup-soda',
            'ham',
            'drumstick',
            'salad',
            'broom',
            'cooking-pot',
            'cheese',
        ] as $icon) {
            $this
                ->actingAs($user)
                ->patch(route('store-sections.icon.update', $section), ['icon' => $icon])
                ->assertSessionHasNoErrors();

            $this->assertDatabaseHas('store_sections', [
                'id' => $section->id,
                'icon' => $icon,
            ]);
        }
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
            ->post(route('store-sections.store'), ['name' => ['Zelenina'], 'colour' => '#123456'])
            ->assertSessionHasErrors('name');
        $this
            ->actingAs($user)
            ->post(route('store-sections.store'), ['name' => 'Zelenina'])
            ->assertSessionHasErrors('colour');
        $this
            ->actingAs($user)
            ->post(route('store-sections.store'), [
                'name' => 'Zelenina',
                'colour' => '#123456',
                'icon' => 'uploaded-file.svg',
            ])
            ->assertSessionHasErrors('icon');

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
