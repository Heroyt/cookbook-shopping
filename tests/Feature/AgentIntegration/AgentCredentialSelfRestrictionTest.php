<?php

declare(strict_types=1);

namespace Tests\Feature\AgentIntegration;

use App\AgentIntegration\Actions\IssueAgentCredential;
use App\AgentIntegration\Models\AgentChangeSet;
use App\AgentIntegration\Models\AgentCredential;
use App\FamilyAccess\AuthorizedFamilyContext;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class AgentCredentialSelfRestrictionTest extends TestCase
{
    public function test_current_credential_can_only_shorten_its_own_expiry(): void
    {
        Carbon::setTestNow('2026-08-12 12:00:00');
        [, $credential, $secret] = $this->credential();
        Notification::fake();

        $this->withToken($secret)->postJson('/api/v1/credential/restrictions', [
            'action' => 'shorten_expiry',
            'expires_at' => '2026-08-12T18:00:00Z',
        ])->assertOk()
            ->assertExactJson([
                'data' => [
                    'credential_id' => $credential->id,
                    'action' => 'shorten_expiry',
                    'status' => 'active',
                    'expires_at' => '2026-08-12T18:00:00Z',
                    'revoked_at' => null,
                    'changed' => true,
                ],
            ]);

        $this->assertTrue($credential->fresh()?->expires_at?->equalTo('2026-08-12 18:00:00') ?? false);

        $this->withToken($secret)->postJson('/api/v1/credential/restrictions', [
            'action' => 'shorten_expiry',
            'expires_at' => '2026-08-13T18:00:00Z',
        ])->assertOk()
            ->assertJsonPath('data.changed', false)
            ->assertJsonPath('data.expires_at', '2026-08-12T18:00:00Z');

        $this->assertTrue($credential->fresh()?->expires_at?->equalTo('2026-08-12 18:00:00') ?? false);
        Notification::assertNothingSent();
    }

    public function test_current_credential_can_immediately_revoke_itself_and_invalidate_previews(): void
    {
        Carbon::setTestNow('2026-08-12 12:00:00');
        [, $credential, $secret] = $this->credential();
        AgentChangeSet::factory()->for($credential, 'credential')->create([
            'family_id' => $credential->family_id,
            'issuer_user_id' => $credential->tokenable_id,
            'status' => 'previewed',
        ]);
        Notification::fake();

        $this->withToken($secret)->postJson('/api/v1/credential/restrictions', [
            'action' => 'revoke',
        ])->assertOk()
            ->assertExactJson([
                'data' => [
                    'credential_id' => $credential->id,
                    'action' => 'revoke',
                    'status' => 'revoked',
                    'expires_at' => '2026-11-10T12:00:00Z',
                    'revoked_at' => '2026-08-12T12:00:00Z',
                    'changed' => true,
                ],
            ]);

        $revoked = $credential->fresh();
        $this->assertSame('self_revoked', $revoked?->revocation_reason);
        $this->assertNull($revoked?->revoked_by_user_id);
        $this->assertSame('invalidated', AgentChangeSet::query()->sole()->status);
        Notification::assertNothingSent();

        Auth::forgetGuards();
        $this->withToken($secret)->getJson('/api/v1/catalog')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'authentication_required');
    }

    public function test_restriction_document_is_closed_and_rejects_invalid_expiry_documents(): void
    {
        Carbon::setTestNow('2026-08-12 12:00:00');
        [, $credential, $secret] = $this->credential();

        foreach ([
            ['action' => 'shorten_expiry'],
            ['action' => 'shorten_expiry', 'expires_at' => '2026-08-12T11:59:59Z'],
            ['action' => 'shorten_expiry', 'expires_at' => '2026-08-12T18:00:00+00:00'],
            ['action' => 'revoke', 'expires_at' => '2026-08-12T18:00:00Z'],
            ['action' => 'revoke', 'credential_id' => $credential->id],
            ['action' => 'rotate'],
            ['action' => []],
        ] as $document) {
            $this->withToken($secret)->postJson('/api/v1/credential/restrictions', $document)
                ->assertUnprocessable()
                ->assertJsonPath('error.code', 'validation_failed');
        }

        $this->assertNull($credential->fresh()?->revoked_at);
        $this->assertTrue($credential->fresh()?->expires_at?->equalTo(now()->addDays(90)) ?? false);
    }

    public function test_invalid_actions_share_one_bounded_rate_limit_bucket(): void
    {
        [, , $secret] = $this->credential();
        config(['agent-integration.rates.credential_restriction_per_minute' => 2]);

        foreach (['invalid-one', 'invalid-two'] as $action) {
            $this->withToken($secret)->postJson('/api/v1/credential/restrictions', ['action' => $action])
                ->assertUnprocessable();
        }

        $this->withToken($secret)->postJson('/api/v1/credential/restrictions', ['action' => 'invalid-three'])
            ->assertTooManyRequests()
            ->assertJsonPath('error.code', 'rate_limit_exceeded');
    }

    public function test_malformed_revoke_documents_cannot_exhaust_the_emergency_revoke_bucket(): void
    {
        [, , $secret] = $this->credential();
        config(['agent-integration.rates.credential_restriction_per_minute' => 2]);

        foreach ([
            ['action' => 'revoke', 'expires_at' => '2026-08-12T18:00:00Z'],
            ['action' => 'revoke', 'unknown' => true],
        ] as $document) {
            $this->withToken($secret)->postJson('/api/v1/credential/restrictions', $document)
                ->assertUnprocessable();
        }

        $this->withToken($secret)->postJson('/api/v1/credential/restrictions', ['action' => 'revoke'])
            ->assertOk()
            ->assertJsonPath('data.status', 'revoked');
    }

    public function test_restriction_requires_a_live_agent_credential_and_is_rate_limited_per_credential(): void
    {
        Carbon::setTestNow('2026-08-12 12:00:00');
        [$issuer, , $secret] = $this->credential();

        $this->postJson('/api/v1/credential/restrictions', ['action' => 'revoke'])
            ->assertUnauthorized();
        $this->actingAs($issuer)->postJson('/api/v1/credential/restrictions', ['action' => 'revoke'])
            ->assertUnauthorized();

        Auth::forgetGuards();
        config(['agent-integration.rates.credential_restriction_per_minute' => 2]);
        foreach (['2026-08-13T12:00:00Z', '2026-08-14T12:00:00Z'] as $expiresAt) {
            $this->withToken($secret)->postJson('/api/v1/credential/restrictions', [
                'action' => 'shorten_expiry',
                'expires_at' => $expiresAt,
            ])->assertOk();
        }

        $this->withToken($secret)->postJson('/api/v1/credential/restrictions', [
            'action' => 'shorten_expiry',
            'expires_at' => '2026-08-15T12:00:00Z',
        ])->assertTooManyRequests()
            ->assertHeader('Retry-After')
            ->assertJsonPath('error.code', 'rate_limit_exceeded');

        $this->withToken($secret)->postJson('/api/v1/credential/restrictions', [
            'action' => 'revoke',
        ])->assertOk()
            ->assertJsonPath('data.status', 'revoked');
    }

    /** @return array{User, AgentCredential, string} */
    private function credential(): array
    {
        $issuer = User::factory()->create();
        $family = Family::factory()->create();
        FamilyMembership::factory()->create([
            'family_id' => $family->id,
            'user_id' => $issuer->id,
        ]);
        $issued = app(IssueAgentCredential::class)->handle(
            new AuthorizedFamilyContext($issuer, $family),
            'Self-restricting agent',
        );

        return [$issuer, $issued->credential, $issued->plainTextSecret];
    }
}
