<?php

declare(strict_types=1);

namespace Tests\Feature\AgentIntegration;

use App\AgentIntegration\Actions\IssueAgentCredential;
use App\AgentIntegration\Actions\RevokeAgentCredential;
use App\AgentIntegration\Actions\RotateAgentCredential;
use App\AgentIntegration\AgentCredentialAbility;
use App\AgentIntegration\Models\AgentCredential;
use App\FamilyAccess\AuthorizedFamilyContext;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

final class AgentCredentialTest extends TestCase
{
    public function test_issuer_can_create_a_family_scoped_credential_with_a_one_time_secret(): void
    {
        Carbon::setTestNow('2026-08-11 12:00:00');
        $issuer = User::factory()->create(['name' => 'Alena Agentová']);
        $family = Family::factory()->create();
        FamilyMembership::factory()->create([
            'family_id' => $family->id,
            'user_id' => $issuer->id,
        ]);

        $issued = app(IssueAgentCredential::class)->handle(
            new AuthorizedFamilyContext($issuer, $family),
            'Kuchyňský pomocník',
            [AgentCredentialAbility::CookbookWrite],
        );

        $credential = $issued->credential;
        $secretParts = explode('|', $issued->plainTextSecret, 2);

        $this->assertCount(2, $secretParts);
        $this->assertSame((string) $credential->id, $secretParts[0]);
        $this->assertSame(hash('sha256', $secretParts[1]), $credential->token);
        $this->assertSame($family->id, $credential->family_id);
        $this->assertSame($issuer->id, $credential->tokenable_id);
        $this->assertSame(User::class, $credential->tokenable_type);
        $this->assertSame('Alena Agentová', $credential->issuer_name);
        $this->assertSame('Kuchyňský pomocník', $credential->name);
        $this->assertSame(
            [AgentCredentialAbility::ContentRead->value, AgentCredentialAbility::CookbookWrite->value],
            $credential->abilities,
        );
        $this->assertTrue($credential->expires_at?->equalTo(now()->addDays(90)) ?? false);
        $this->assertNull($credential->revoked_at);
        $this->assertArrayNotHasKey('token', $credential->toArray());
    }

    public function test_credential_expiry_must_be_in_the_future_and_at_most_one_year_away(): void
    {
        Carbon::setTestNow('2026-08-11 12:00:00');
        [$context] = $this->familyContext();

        foreach ([now()->subSecond(), now()->addYear()->addSecond()] as $invalidExpiry) {
            try {
                app(IssueAgentCredential::class)->handle($context, 'Agent', [], $invalidExpiry);
                $this->fail('An invalid credential expiry was accepted.');
            } catch (InvalidArgumentException $exception) {
                $this->assertSame(
                    'Agent credential expiry must be in the future and no more than one year away.',
                    $exception->getMessage(),
                );
            }
        }

        $issued = app(IssueAgentCredential::class)->handle($context, 'Agent', [], now()->addYear());

        $this->assertTrue($issued->credential->expires_at?->equalTo(now()->addYear()) ?? false);
    }

    public function test_any_current_family_member_can_revoke_and_revoked_metadata_is_retained(): void
    {
        Carbon::setTestNow('2026-08-11 12:00:00');
        [$issuerContext, $family, $issuer] = $this->familyContext();
        $member = User::factory()->create();
        FamilyMembership::factory()->create(['family_id' => $family->id, 'user_id' => $member->id]);
        $issued = app(IssueAgentCredential::class)->handle($issuerContext, 'Agent');

        app(RevokeAgentCredential::class)->handle(
            new AuthorizedFamilyContext($member, $family),
            $issued->credential->id,
        );

        $credential = AgentCredential::query()->findOrFail($issued->credential->id);
        $this->assertTrue($credential->revoked_at?->equalTo(now()) ?? false);
        $this->assertSame($member->id, $credential->revoked_by_user_id);
        $this->assertSame('revoked', $credential->revocation_reason);
        $this->assertSame($issuer->id, $credential->tokenable_id);
        $this->assertSame('Agent', $credential->name);
        $this->assertNull(AgentCredential::findToken($issued->plainTextSecret));
    }

    public function test_only_issuer_can_rotate_and_rotation_immediately_replaces_the_secret(): void
    {
        Carbon::setTestNow('2026-08-11 12:00:00');
        [$issuerContext, $family] = $this->familyContext();
        $member = User::factory()->create();
        FamilyMembership::factory()->create(['family_id' => $family->id, 'user_id' => $member->id]);
        $issued = app(IssueAgentCredential::class)->handle(
            $issuerContext,
            'Agent',
            [AgentCredentialAbility::PlanningWrite],
        );

        $this->expectException(AuthorizationException::class);
        app(RotateAgentCredential::class)->handle(
            new AuthorizedFamilyContext($member, $family),
            $issued->credential->id,
        );
    }

    public function test_issuer_rotation_revokes_old_credential_and_retains_lineage(): void
    {
        Carbon::setTestNow('2026-08-11 12:00:00');
        [$context] = $this->familyContext();
        $issued = app(IssueAgentCredential::class)->handle(
            $context,
            'Agent',
            [AgentCredentialAbility::PlanningWrite],
        );

        $replacement = app(RotateAgentCredential::class)->handle($context, $issued->credential->id);
        $oldCredential = AgentCredential::query()->findOrFail($issued->credential->id);

        $this->assertNotSame($issued->plainTextSecret, $replacement->plainTextSecret);
        $this->assertNull(AgentCredential::findToken($issued->plainTextSecret));
        $this->assertSame($replacement->credential->id, AgentCredential::findToken($replacement->plainTextSecret)?->id);
        $this->assertSame($replacement->credential->id, $oldCredential->rotated_to_id);
        $this->assertSame('rotated', $oldCredential->revocation_reason);
        $this->assertSame($context->user->id, $oldCredential->revoked_by_user_id);
        $this->assertSame($oldCredential->abilities, $replacement->credential->abilities);
        $this->assertSame($oldCredential->name, $replacement->credential->name);
    }

    public function test_credential_is_invalid_as_soon_as_issuer_leaves_family(): void
    {
        [$context, $family, $issuer] = $this->familyContext();
        $issued = app(IssueAgentCredential::class)->handle($context, 'Agent');

        FamilyMembership::query()
            ->where('family_id', $family->id)
            ->where('user_id', $issuer->id)
            ->delete();

        $this->assertNull(AgentCredential::findToken($issued->plainTextSecret));
        $this->assertDatabaseHas('agent_credentials', [
            'id' => $issued->credential->id,
            'issuer_name' => $issuer->name,
            'revoked_at' => null,
        ]);
    }

    public function test_deleted_issuer_invalidates_secret_without_deleting_retained_metadata(): void
    {
        [$context, , $issuer] = $this->familyContext();
        $issued = app(IssueAgentCredential::class)->handle($context, 'Agent po bývalém členovi');

        $issuer->delete();

        $this->assertNull(AgentCredential::findToken($issued->plainTextSecret));
        $this->assertDatabaseHas('agent_credentials', [
            'id' => $issued->credential->id,
            'issuer_name' => 'Alena Agentová',
            'name' => 'Agent po bývalém členovi',
        ]);
    }

    /**
     * @return array{AuthorizedFamilyContext, Family, User}
     */
    private function familyContext(): array
    {
        $issuer = User::factory()->create(['name' => 'Alena Agentová']);
        $family = Family::factory()->create();
        FamilyMembership::factory()->create([
            'family_id' => $family->id,
            'user_id' => $issuer->id,
        ]);

        return [new AuthorizedFamilyContext($issuer, $family), $family, $issuer];
    }
}
