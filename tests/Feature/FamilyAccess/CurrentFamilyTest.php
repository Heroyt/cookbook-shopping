<?php

declare(strict_types=1);

namespace Tests\Feature\FamilyAccess;

use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class CurrentFamilyTest extends TestCase
{
    public function test_creating_a_family_selects_it_as_the_users_current_family(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('families.store'), ['name' => 'Weekend Kitchen'])
            ->assertRedirect(route('families.index'));

        $family = Family::query()->sole();

        $this->assertSame($family->id, $user->fresh()->current_family_id);
    }

    public function test_user_can_select_a_family_from_their_memberships(): void
    {
        $user = User::factory()->create();
        $firstFamily = $this->createMembership($user);
        $secondFamily = $this->createMembership($user);
        $user->forceFill(['current_family_id' => $firstFamily->id])->save();

        $this
            ->actingAs($user)
            ->put(route('current-family.update', $secondFamily))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame($secondFamily->id, $user->fresh()->current_family_id);
    }

    public function test_user_cannot_select_a_family_without_a_membership(): void
    {
        $user = User::factory()->create();
        $memberFamily = $this->createMembership($user);
        $otherFamily = Family::factory()->create();
        $user->forceFill(['current_family_id' => $memberFamily->id])->save();

        $this
            ->actingAs($user)
            ->put(route('current-family.update', $otherFamily))
            ->assertNotFound();

        $this->assertSame($memberFamily->id, $user->fresh()->current_family_id);
    }

    public function test_invalid_current_family_falls_back_to_the_first_remaining_membership(): void
    {
        $user = User::factory()->create();
        $staleFamily = $this->createMembership($user);
        $remainingFamily = $this->createMembership($user);
        $user->forceFill(['current_family_id' => $staleFamily->id])->save();
        $user->familyMemberships()->where('family_id', $staleFamily->id)->delete();

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('currentFamily.id', $remainingFamily->id)
                ->has('families', 1)
                ->where('families.0.id', $remainingFamily->id));

        $this->assertSame($remainingFamily->id, $user->fresh()->current_family_id);
    }

    private function createMembership(User $user): Family
    {
        $family = Family::factory()->create();

        FamilyMembership::factory()->create([
            'family_id' => $family->id,
            'user_id' => $user->id,
        ]);

        return $family;
    }
}
