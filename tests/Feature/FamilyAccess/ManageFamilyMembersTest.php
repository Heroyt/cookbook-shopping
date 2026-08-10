<?php

declare(strict_types=1);

namespace Tests\Feature\FamilyAccess;

use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class ManageFamilyMembersTest extends TestCase
{
    public function test_any_member_can_add_an_existing_user_by_email(): void
    {
        $creator = User::factory()->create();
        $actor = User::factory()->create();
        $newMember = User::factory()->create(['email' => 'member@example.com']);
        $family = $this->createFamilyWithMembers($creator, $actor);
        $this->selectCurrentFamily($actor, $family);

        $this
            ->actingAs($actor)
            ->post(route('current-family.members.store'), ['email' => '  MEMBER@example.com '])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('families.index'));

        $this->assertTrue($family->members()->whereKey($newMember->id)->exists());
    }

    public function test_unknown_email_returns_a_validation_error_without_creating_a_user(): void
    {
        $actor = User::factory()->create();
        $family = $this->createFamilyWithMembers($actor);
        $this->selectCurrentFamily($actor, $family);

        $this
            ->actingAs($actor)
            ->post(route('current-family.members.store'), ['email' => 'missing@example.com'])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount((new User())->getTable(), 1);
        $this->assertCount(1, $family->members()->get());
    }

    public function test_existing_member_cannot_be_added_twice(): void
    {
        $actor = User::factory()->create();
        $family = $this->createFamilyWithMembers($actor);
        $this->selectCurrentFamily($actor, $family);

        $this
            ->actingAs($actor)
            ->post(route('current-family.members.store'), ['email' => $actor->email])
            ->assertSessionHasErrors('email');

        $this->assertCount(1, $family->members()->get());
    }

    public function test_family_page_exposes_only_the_current_familys_members(): void
    {
        $actor = User::factory()->create();
        $currentMember = User::factory()->create();
        $otherMember = User::factory()->create();
        $currentFamily = $this->createFamilyWithMembers($actor, $currentMember);
        $this->createFamilyWithMembers($actor, $otherMember);
        $this->selectCurrentFamily($actor, $currentFamily);

        $this
            ->actingAs($actor)
            ->get(route('families.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->component('families/Index')
                ->where('family.id', $currentFamily->id)
                ->has('family.members', 2)
                ->where('family.members.0.id', $actor->id)
                ->where('family.members.1.id', $currentMember->id));
    }

    public function test_member_can_remove_another_member_and_their_current_family_falls_back(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        $family = $this->createFamilyWithMembers($actor, $target);
        $fallbackFamily = $this->createFamilyWithMembers($target);
        $this->selectCurrentFamily($actor, $family);
        $this->selectCurrentFamily($target, $family);

        $this
            ->actingAs($actor)
            ->delete(route('current-family.members.destroy', $target))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('families.index'));

        $this->assertFalse($family->members()->whereKey($target->id)->exists());
        $this->assertSame($fallbackFamily->id, $target->fresh()->current_family_id);
    }

    public function test_member_can_leave_when_another_member_remains(): void
    {
        $actor = User::factory()->create();
        $remainingMember = User::factory()->create();
        $family = $this->createFamilyWithMembers($actor, $remainingMember);
        $this->selectCurrentFamily($actor, $family);

        $this
            ->actingAs($actor)
            ->delete(route('current-family.members.destroy', $actor))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('families.index'));

        $this->assertFalse($family->members()->whereKey($actor->id)->exists());
        $this->assertNull($actor->fresh()->current_family_id);
        $this->assertTrue($family->members()->whereKey($remainingMember->id)->exists());
    }

    public function test_final_member_cannot_leave_or_be_removed(): void
    {
        $actor = User::factory()->create();
        $family = $this->createFamilyWithMembers($actor);
        $this->selectCurrentFamily($actor, $family);

        $this
            ->actingAs($actor)
            ->delete(route('current-family.members.destroy', $actor))
            ->assertSessionHasErrors('membership');

        $this->assertTrue($family->members()->whereKey($actor->id)->exists());
        $this->assertSame($family->id, $actor->fresh()->current_family_id);
    }

    public function test_current_family_scope_cannot_remove_a_user_from_another_family(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        $currentFamily = $this->createFamilyWithMembers($actor);
        $otherFamily = $this->createFamilyWithMembers($target);
        $this->selectCurrentFamily($actor, $currentFamily);

        $this
            ->actingAs($actor)
            ->delete(route('current-family.members.destroy', $target))
            ->assertNotFound();

        $this->assertTrue($otherFamily->members()->whereKey($target->id)->exists());
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
