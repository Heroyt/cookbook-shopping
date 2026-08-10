<?php

declare(strict_types=1);

namespace Tests\Feature\FamilyAccess;

use App\Cookbook\Models\Store;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Tests\TestCase;

final class DeleteFamilyTest extends TestCase
{
    public function test_any_member_can_delete_the_current_family_with_its_exact_name(): void
    {
        $actor = User::factory()->create();
        $otherMember = User::factory()->create();
        $family = $this->createFamilyWithMembers('Sunday Suppers', $actor, $otherMember);
        Store::factory()->for($family)->create();
        $fallbackFamily = $this->createFamilyWithMembers('Weekdays', $actor);
        $this->selectCurrentFamily($actor, $family);
        $this->selectCurrentFamily($otherMember, $family);

        $this
            ->actingAs($actor)
            ->delete(route('current-family.destroy'), ['family_name' => 'Sunday Suppers'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('families.index'));

        $this->assertModelMissing($family);
        $this->assertDatabaseMissing((new FamilyMembership())->getTable(), ['family_id' => $family->id]);
        $this->assertDatabaseMissing('stores', ['family_id' => $family->id]);
        $this->assertSame($fallbackFamily->id, $actor->fresh()->current_family_id);
        $this->assertNull($otherMember->fresh()->current_family_id);
    }

    public function test_family_name_must_match_exactly(): void
    {
        $actor = User::factory()->create();
        $family = $this->createFamilyWithMembers('Sunday Suppers', $actor);
        $this->selectCurrentFamily($actor, $family);

        $this
            ->actingAs($actor)
            ->delete(route('current-family.destroy'), ['family_name' => 'sunday suppers'])
            ->assertSessionHasErrors('family_name');

        $this->assertModelExists($family);
        $this->assertSame($family->id, $actor->fresh()->current_family_id);
    }

    public function test_only_the_current_family_can_be_deleted(): void
    {
        $actor = User::factory()->create();
        $currentFamily = $this->createFamilyWithMembers('Current', $actor);
        $otherFamily = $this->createFamilyWithMembers('Other', $actor);
        $this->selectCurrentFamily($actor, $currentFamily);

        $this
            ->actingAs($actor)
            ->delete(route('current-family.destroy'), ['family_name' => $otherFamily->name])
            ->assertSessionHasErrors('family_name');

        $this->assertModelExists($currentFamily);
        $this->assertModelExists($otherFamily);
    }

    private function createFamilyWithMembers(string $name, User ...$users): Family
    {
        $family = Family::factory()->create(['name' => $name]);

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
