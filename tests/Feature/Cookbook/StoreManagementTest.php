<?php

declare(strict_types=1);

namespace Tests\Feature\Cookbook;

use App\Cookbook\Models\Store;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class StoreManagementTest extends TestCase
{
    public function test_guests_cannot_manage_stores(): void
    {
        $this->get(route('stores.index'))->assertRedirect(route('login'));
        $this->post(route('stores.store'), ['name' => 'Market'])->assertRedirect(route('login'));
        $this->patch(route('stores.update', 1), ['name' => 'Market'])->assertRedirect(route('login'));
    }

    public function test_each_member_can_create_and_list_stores_in_their_shared_family(): void
    {
        $firstMember = User::factory()->create();
        $secondMember = User::factory()->create();
        $family = $this->createFamilyWithMembers($firstMember, $secondMember);
        $this->selectCurrentFamily($firstMember, $family);
        $this->selectCurrentFamily($secondMember, $family);

        $this
            ->actingAs($firstMember)
            ->post(route('stores.store'), ['name' => '  Weekend   Market  '])
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Store created.',
            ])
            ->assertRedirect(route('stores.index'));

        $store = Store::query()->sole();

        $this->assertSame($family->id, $store->family_id);
        $this->assertSame('Weekend Market', $store->name);
        $this->assertSame('weekend market', $store->normalized_name);

        $this
            ->actingAs($secondMember)
            ->get(route('stores.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->component('stores/Index')
                ->has('stores', 1)
                ->where('stores.0.id', $store->id)
                ->where('stores.0.name', 'Weekend Market'));

        $this
            ->actingAs($secondMember)
            ->post(route('stores.store'), ['name' => 'Daily Market'])
            ->assertSessionHasNoErrors();

        $this
            ->actingAs($firstMember)
            ->get(route('stores.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('stores', 2)
                ->where('stores.0.name', 'Daily Market')
                ->where('stores.1.name', 'Weekend Market'));
    }

    public function test_each_member_can_rename_a_store_in_their_shared_family(): void
    {
        $firstMember = User::factory()->create();
        $secondMember = User::factory()->create();
        $family = $this->createFamilyWithMembers($firstMember, $secondMember);
        $this->selectCurrentFamily($firstMember, $family);
        $this->selectCurrentFamily($secondMember, $family);
        $store = Store::factory()->for($family)->create(['name' => 'Weekend Market']);

        $this
            ->actingAs($secondMember)
            ->patch(route('stores.update', $store), ['name' => '  Daily   Market  '])
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Store renamed.',
            ])
            ->assertRedirect(route('stores.index'));

        $store->refresh();

        $this->assertSame('Daily Market', $store->name);
        $this->assertSame('daily market', $store->normalized_name);

        $this
            ->actingAs($firstMember)
            ->get(route('stores.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('stores', 1)
                ->where('stores.0.id', $store->id)
                ->where('stores.0.name', 'Daily Market'));
    }

    public function test_store_reads_are_scoped_to_the_current_family(): void
    {
        $user = User::factory()->create();
        $currentFamily = $this->createFamilyWithMembers($user);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($user, $currentFamily);
        $visibleStore = Store::factory()->for($currentFamily)->create(['name' => 'Visible Store']);
        Store::factory()->for($otherFamily)->create(['name' => 'Private Store']);

        $this
            ->actingAs($user)
            ->get(route('stores.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('stores', 1)
                ->where('stores.0.id', $visibleStore->id)
                ->where('stores.0.name', 'Visible Store'));
    }

    public function test_client_supplied_family_identifier_cannot_redirect_a_store_write(): void
    {
        $user = User::factory()->create();
        $currentFamily = $this->createFamilyWithMembers($user);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($user, $currentFamily);

        $this
            ->actingAs($user)
            ->post(route('stores.store'), [
                'name' => 'Scoped Store',
                'family_id' => $otherFamily->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('stores', [
            'family_id' => $currentFamily->id,
            'name' => 'Scoped Store',
        ]);
        $this->assertDatabaseMissing('stores', [
            'family_id' => $otherFamily->id,
            'name' => 'Scoped Store',
        ]);
    }

    public function test_store_renames_are_scoped_to_the_current_family(): void
    {
        $user = User::factory()->create();
        $currentFamily = $this->createFamilyWithMembers($user);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($user, $currentFamily);
        $currentStore = Store::factory()->for($currentFamily)->create(['name' => 'Current Store']);
        $otherStore = Store::factory()->for($otherFamily)->create(['name' => 'Other Store']);

        $this
            ->actingAs($user)
            ->patch(route('stores.update', $currentStore), [
                'name' => 'Renamed Store',
                'family_id' => $otherFamily->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('stores', [
            'id' => $currentStore->id,
            'family_id' => $currentFamily->id,
            'name' => 'Renamed Store',
        ]);

        $this
            ->actingAs($user)
            ->patch(route('stores.update', $otherStore), ['name' => 'Leaked Store'])
            ->assertNotFound();

        $this->assertDatabaseHas('stores', [
            'id' => $otherStore->id,
            'family_id' => $otherFamily->id,
            'name' => 'Other Store',
        ]);
    }

    public function test_normalized_store_name_is_unique_within_a_family(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        Store::factory()->for($family)->create(['name' => 'Weekend Market']);

        $this
            ->actingAs($user)
            ->post(route('stores.store'), ['name' => '  WEEKEND   market '])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('stores', 1);
    }

    public function test_store_cannot_be_renamed_to_an_existing_normalized_name_in_its_family(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $store = Store::factory()->for($family)->create(['name' => 'Weekend Market']);
        Store::factory()->for($family)->create(['name' => 'Daily Market']);

        $this
            ->actingAs($user)
            ->patch(route('stores.update', $store), ['name' => '  DAILY   market '])
            ->assertSessionHasErrors('name');

        $store->refresh();

        $this->assertSame('Weekend Market', $store->name);
        $this->assertSame('weekend market', $store->normalized_name);
        $this->assertDatabaseCount('stores', 2);
    }

    public function test_same_normalized_store_name_is_allowed_in_another_family(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($user, $family);
        Store::factory()->for($otherFamily)->create(['name' => 'Shared Name']);

        $this
            ->actingAs($user)
            ->post(route('stores.store'), ['name' => 'SHARED NAME'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('stores', [
            'family_id' => $family->id,
            'normalized_name' => 'shared name',
        ]);
    }

    public function test_accent_distinct_store_names_remain_distinct_within_a_family(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        Store::factory()->for($family)->create(['name' => 'Cafe']);

        $this
            ->actingAs($user)
            ->post(route('stores.store'), ['name' => 'Café'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('stores', 2);
    }

    public function test_store_name_is_required_and_cannot_exceed_the_database_limit(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);

        $this->actingAs($user)->post(route('stores.store'), ['name' => '   '])->assertSessionHasErrors('name');
        $this->actingAs($user)->post(route('stores.store'), ['name' => str_repeat('a', 256)])->assertSessionHasErrors('name');

        $this->assertDatabaseCount('stores', 0);
    }

    public function test_renamed_store_name_is_required_and_cannot_exceed_the_database_limit(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $this->selectCurrentFamily($user, $family);
        $store = Store::factory()->for($family)->create(['name' => 'Weekend Market']);

        $this->actingAs($user)->patch(route('stores.update', $store), ['name' => '   '])->assertSessionHasErrors('name');
        $this->actingAs($user)->patch(route('stores.update', $store), ['name' => str_repeat('a', 256)])->assertSessionHasErrors('name');

        $store->refresh();

        $this->assertSame('Weekend Market', $store->name);
        $this->assertSame('weekend market', $store->normalized_name);
    }

    public function test_store_routes_require_a_current_family(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('stores.index'))->assertNotFound();
        $this->actingAs($user)->post(route('stores.store'), ['name' => 'No Family'])->assertNotFound();
        $this->actingAs($user)->patch(route('stores.update', 1), ['name' => 'No Family'])->assertNotFound();

        $this->assertDatabaseCount('stores', 0);
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
