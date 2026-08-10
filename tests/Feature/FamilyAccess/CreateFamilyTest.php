<?php

declare(strict_types=1);

namespace Tests\Feature\FamilyAccess;

use App\FamilyAccess\Actions\CreateFamily;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class CreateFamilyTest extends TestCase
{
    public function test_guests_cannot_access_family_creation(): void
    {
        $this->get(route('families.create'))->assertRedirect(route('login'));

        $this
            ->post(route('families.store'), ['name' => 'Weekend Kitchen'])
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_the_family_creation_page(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('families.create'));

        $response
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->component('families/Create'));
    }

    public function test_authenticated_user_can_create_a_family_and_become_its_first_member(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('families.store'), [
                'name' => '  Weekend   Kitchen  ',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Family created.',
            ])
            ->assertRedirect(route('dashboard'));

        $family = $user->families()->sole();

        $this->assertSame('Weekend Kitchen', $family->name);
        $this->assertCount(1, $family->members);
        $this->assertTrue($family->members->contains($user));
    }

    public function test_family_name_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('families.store'), ['name' => '   ']);

        $response->assertSessionHasErrors('name');
        $this->assertCount(0, $user->families()->get());
    }

    public function test_family_name_cannot_exceed_the_database_column_limit(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('families.store'), ['name' => str_repeat('a', 256)]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('families', 0);
    }

    public function test_family_creation_rolls_back_if_the_initial_membership_cannot_be_created(): void
    {
        $user = User::factory()->create();
        $createFamily = app(CreateFamily::class);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER fail_family_membership_insert
            BEFORE INSERT ON family_memberships
            BEGIN
                SELECT RAISE(FAIL, 'forced membership failure');
            END
            SQL);

        try {
            $createFamily->handle($user, 'Rollback Family');
            $this->fail('Expected the initial membership insert to fail.');
        } catch (QueryException) {
            $this->assertDatabaseMissing('families', ['name' => 'Rollback Family']);
        }
    }

    public function test_a_user_cannot_have_duplicate_memberships_in_a_family(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create();

        FamilyMembership::factory()->create([
            'family_id' => $family->id,
            'user_id' => $user->id,
        ]);

        $this->expectException(QueryException::class);

        FamilyMembership::factory()->create([
            'family_id' => $family->id,
            'user_id' => $user->id,
        ]);
    }
}
