<?php

declare(strict_types=1);

namespace Tests\Feature\FamilyAccess;

use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Tests\TestCase;

final class AccountDeletionTest extends TestCase
{
    public function test_account_deletion_is_blocked_while_the_user_is_a_family_final_member(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create();
        $membership = FamilyMembership::factory()->create([
            'family_id' => $family->id,
            'user_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasErrors('account')
            ->assertRedirect(route('profile.edit'));

        $this->assertAuthenticatedAs($user);
        $this->assertModelExists($user);
        $this->assertModelExists($family);
        $this->assertModelExists($membership);
    }

    public function test_account_deletion_succeeds_when_every_family_has_another_member(): void
    {
        $user = User::factory()->create();
        $otherMember = User::factory()->create();
        $family = Family::factory()->create();
        $departingMembership = FamilyMembership::factory()->create([
            'family_id' => $family->id,
            'user_id' => $user->id,
        ]);
        $remainingMembership = FamilyMembership::factory()->create([
            'family_id' => $family->id,
            'user_id' => $otherMember->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('profile.destroy'), [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertModelMissing($user);
        $this->assertModelExists($family);
        $this->assertModelMissing($departingMembership);
        $this->assertModelExists($remainingMembership);
        $this->assertModelExists($otherMember);
    }

    public function test_one_final_membership_blocks_deletion_across_all_families(): void
    {
        $user = User::factory()->create();
        $otherMember = User::factory()->create();
        $sharedFamily = Family::factory()->create();
        $soleMemberFamily = Family::factory()->create();

        foreach ([$sharedFamily, $soleMemberFamily] as $family) {
            FamilyMembership::factory()->create([
                'family_id' => $family->id,
                'user_id' => $user->id,
            ]);
        }

        FamilyMembership::factory()->create([
            'family_id' => $sharedFamily->id,
            'user_id' => $otherMember->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasErrors('account')
            ->assertRedirect(route('profile.edit'));

        $this->assertAuthenticatedAs($user);
        $this->assertModelExists($user);
        $this->assertCount(2, $user->familyMemberships()->get());
        $this->assertCount(2, $sharedFamily->memberships()->get());
        $this->assertCount(1, $soleMemberFamily->memberships()->get());
    }
}
