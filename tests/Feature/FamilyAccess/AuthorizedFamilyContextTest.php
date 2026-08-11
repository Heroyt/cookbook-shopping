<?php

declare(strict_types=1);

namespace Tests\Feature\FamilyAccess;

use App\Cookbook\Actions\CreateStore;
use App\Cookbook\Models\Store;
use App\FamilyAccess\AuthorizedFamilyContext;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

final class AuthorizedFamilyContextTest extends TestCase
{
    public function test_it_can_only_be_created_for_a_live_family_membership(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create();
        FamilyMembership::factory()->create([
            'user_id' => $user->id,
            'family_id' => $family->id,
        ]);

        $context = new AuthorizedFamilyContext($user, $family);

        $this->assertTrue($context->user->is($user));
        $this->assertTrue($context->family->is($family));

        $family->memberships()->whereBelongsTo($user)->delete();

        $this->expectException(ModelNotFoundException::class);

        new AuthorizedFamilyContext($user, $family);
    }

    public function test_an_action_uses_the_authorized_family_without_changing_current_family(): void
    {
        $user = User::factory()->create();
        $currentFamily = Family::factory()->create();
        $authorizedFamily = Family::factory()->create();
        foreach ([$currentFamily, $authorizedFamily] as $family) {
            FamilyMembership::factory()->create([
                'user_id' => $user->id,
                'family_id' => $family->id,
            ]);
        }
        $user->forceFill(['current_family_id' => $currentFamily->id])->save();

        $store = app(CreateStore::class)->handle(
            new AuthorizedFamilyContext($user, $authorizedFamily),
            'Agent Market',
        );

        $this->assertSame($authorizedFamily->id, $store->family_id);
        $this->assertSame($currentFamily->id, $user->fresh()->current_family_id);
        $this->assertSame($store->id, Store::query()->whereBelongsTo($authorizedFamily)->sole()->id);
        $this->assertDatabaseMissing('stores', [
            'family_id' => $currentFamily->id,
            'name' => 'Agent Market',
        ]);
    }
}
