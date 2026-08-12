<?php

declare(strict_types=1);

namespace Tests\Feature\AgentIntegration;

use App\AgentIntegration\Actions\IssueAgentCredential;
use App\AgentIntegration\AgentCredentialAbility;
use App\AgentIntegration\Models\AgentCredential;
use App\FamilyAccess\AuthorizedFamilyContext;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class AgentCredentialManagementTest extends TestCase
{
    public function test_guests_cannot_manage_agent_credentials(): void
    {
        $this->get(route('agent-credentials.index'))->assertRedirect(route('login'));
        $this->post(route('agent-credentials.store'))->assertRedirect(route('login'));
        $this->post(route('agent-credentials.rotate', 1))->assertRedirect(route('login'));
        $this->delete(route('agent-credentials.destroy', 1))->assertRedirect(route('login'));
    }

    public function test_each_member_sees_non_secret_metadata_only_for_current_family(): void
    {
        [$issuer, $family] = $this->memberWithCurrentFamily('Alena');
        $member = User::factory()->create(['name' => 'Boris']);
        FamilyMembership::factory()->create(['family_id' => $family->id, 'user_id' => $member->id]);
        $member->forceFill(['current_family_id' => $family->id])->save();
        $visible = app(IssueAgentCredential::class)->handle(
            new AuthorizedFamilyContext($issuer, $family),
            'Kuchyňský agent',
            [AgentCredentialAbility::CookbookWrite],
        )->credential;
        [$otherIssuer, $otherFamily] = $this->memberWithCurrentFamily('Cyril');
        app(IssueAgentCredential::class)->handle(
            new AuthorizedFamilyContext($otherIssuer, $otherFamily),
            'Soukromý agent',
        );

        $this->actingAs($member)->get(route('agent-credentials.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->component('agent-credentials/Index')
                ->has('credentials', 1)
                ->where('credentials.0.id', $visible->id)
                ->where('credentials.0.name', 'Kuchyňský agent')
                ->where('credentials.0.issuerName', 'Alena')
                ->where('credentials.0.abilities', ['content:read', 'cookbook:write'])
                ->where('credentials.0.isIssuer', false)
                ->missing('credentials.0.token'));
    }

    public function test_management_page_exposes_environment_derived_agent_connection_urls_without_a_secret(): void
    {
        [$issuer] = $this->memberWithCurrentFamily('Alena');

        $this->actingAs($issuer)->get(route('agent-credentials.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('agentConnection.applicationUrl', url('/'))
                ->where('agentConnection.agentAccessUrl', route('agent-credentials.index'))
                ->where('agentConnection.apiBaseUrl', url('/api/v1'))
                ->where('agentConnection.openApiUrl', url('/docs/agent-api/v1/openapi.json'))
                ->missing('agentConnection.secret'));
    }

    public function test_create_and_rotate_require_recent_password_confirmation_and_only_return_secret_once(): void
    {
        [$issuer, $family] = $this->memberWithCurrentFamily('Alena');

        $this->actingAs($issuer)->post(route('agent-credentials.store'), [
            'name' => 'Kuchyňský agent',
            'abilities' => ['cookbook:write'],
        ])->assertRedirect(route('password.confirm'));

        $response = $this->actingAs($issuer)
            ->withSession(['auth.password_confirmed_at' => now()->unix()])
            ->post(route('agent-credentials.store'), [
                'name' => 'Kuchyňský agent',
                'abilities' => ['cookbook:write'],
                'expires_at' => now()->addDays(60)->toDateString(),
            ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('agent-credentials.index'))
            ->assertInertiaFlash('agentCredentialSecret.name', 'Kuchyňský agent');

        $credential = AgentCredential::query()->sole();
        $secret = Arr::get(app(Session::class)->get(SessionKey::FLASH_DATA, []), 'agentCredentialSecret.secret');
        $this->assertIsString($secret);
        $this->assertSame($credential->id, AgentCredential::findToken($secret)?->id);
        $this->assertTrue($credential->expires_at?->equalTo(now()->addDays(61)->startOfDay()) ?? false);

        $this->actingAs($issuer)->get(route('agent-credentials.index'))
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->hasFlash('agentCredentialSecret.secret', $secret)
                ->where('credentials.0.isIssuer', true));
        $this->actingAs($issuer)->get(route('agent-credentials.index'))
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->missingFlash('agentCredentialSecret'));

        $this->actingAs($issuer)
            ->withSession(['auth.password_confirmed_at' => 0])
            ->post(route('agent-credentials.rotate', $credential))
            ->assertRedirect(route('password.confirm'));

        $rotation = $this->actingAs($issuer)
            ->withSession(['auth.password_confirmed_at' => now()->unix()])
            ->post(route('agent-credentials.rotate', $credential));

        $rotation->assertRedirect(route('agent-credentials.index'))
            ->assertInertiaFlash('agentCredentialSecret.name', 'Kuchyňský agent');
        $replacementSecret = Arr::get(app(Session::class)->get(SessionKey::FLASH_DATA, []), 'agentCredentialSecret.secret');
        $this->assertIsString($replacementSecret);
        $this->assertNull(AgentCredential::findToken($secret));
        $this->assertNotNull(AgentCredential::findToken($replacementSecret));
    }

    public function test_create_accepts_exact_duration_presets_and_an_inclusive_custom_date(): void
    {
        Carbon::setTestNow('2026-08-12 12:00:00');
        [$issuer] = $this->memberWithCurrentFamily('Alena');
        $confirmedPassword = ['auth.password_confirmed_at' => now()->unix()];

        $this->actingAs($issuer)
            ->withSession($confirmedPassword)
            ->post(route('agent-credentials.store'), [
                'name' => 'Týdenní agent',
                'validity_days' => 7,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('agent-credentials.index'));

        $this->assertTrue(
            AgentCredential::query()
                ->where('name', 'Týdenní agent')
                ->sole()
                ->expires_at?->equalTo('2026-08-19 12:00:00') ?? false,
        );

        $this->actingAs($issuer)
            ->withSession($confirmedPassword)
            ->post(route('agent-credentials.store'), [
                'name' => 'Agent s vlastním datem',
                'expires_at' => '2026-08-20',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('agent-credentials.index'));

        $this->assertTrue(
            AgentCredential::query()
                ->where('name', 'Agent s vlastním datem')
                ->sole()
                ->expires_at?->equalTo('2026-08-21 00:00:00') ?? false,
        );

        $this->actingAs($issuer)
            ->withSession($confirmedPassword)
            ->post(route('agent-credentials.store'), [
                'name' => 'Neplatná délka',
                'validity_days' => 2,
            ])
            ->assertSessionHasErrors('validity_days');
    }

    public function test_only_issuer_can_rotate_but_each_current_member_can_revoke(): void
    {
        [$issuer, $family] = $this->memberWithCurrentFamily('Alena');
        $member = User::factory()->create();
        FamilyMembership::factory()->create(['family_id' => $family->id, 'user_id' => $member->id]);
        $member->forceFill(['current_family_id' => $family->id])->save();
        $credential = app(IssueAgentCredential::class)->handle(
            new AuthorizedFamilyContext($issuer, $family),
            'Agent',
        )->credential;

        $this->actingAs($member)
            ->withSession(['auth.password_confirmed_at' => now()->unix()])
            ->post(route('agent-credentials.rotate', $credential))
            ->assertForbidden();

        $this->actingAs($member)
            ->delete(route('agent-credentials.destroy', $credential))
            ->assertRedirect(route('agent-credentials.index'))
            ->assertInertiaFlash('toast.message', 'Přístup agenta byl odvolán.');

        $this->assertSame($member->id, $credential->fresh()?->revoked_by_user_id);
    }

    public function test_management_mutations_cannot_cross_current_family_scope(): void
    {
        [$user, $currentFamily] = $this->memberWithCurrentFamily('Alena');
        $otherFamily = Family::factory()->create();
        FamilyMembership::factory()->create(['family_id' => $otherFamily->id, 'user_id' => $user->id]);
        $otherCredential = app(IssueAgentCredential::class)->handle(
            new AuthorizedFamilyContext($user, $otherFamily),
            'Jiný agent',
        )->credential;

        $this->actingAs($user)
            ->delete(route('agent-credentials.destroy', $otherCredential), ['family_id' => $otherFamily->id])
            ->assertNotFound();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => now()->unix()])
            ->post(route('agent-credentials.store'), [
                'name' => 'Nový agent',
                'family_id' => $otherFamily->id,
            ])->assertRedirect(route('agent-credentials.index'));

        $this->assertDatabaseHas('agent_credentials', [
            'name' => 'Nový agent',
            'family_id' => $currentFamily->id,
        ]);
        $this->assertNull($otherCredential->fresh()?->revoked_at);
    }

    /** @return array{User, Family} */
    private function memberWithCurrentFamily(string $name): array
    {
        $user = User::factory()->create(['name' => $name]);
        $family = Family::factory()->create();
        FamilyMembership::factory()->create(['family_id' => $family->id, 'user_id' => $user->id]);
        $user->forceFill(['current_family_id' => $family->id])->save();

        return [$user, $family];
    }
}
