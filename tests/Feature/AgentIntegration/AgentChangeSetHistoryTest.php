<?php

declare(strict_types=1);

namespace Tests\Feature\AgentIntegration;

use App\AgentIntegration\Actions\IssueAgentCredential;
use App\AgentIntegration\Models\AgentChangeSet;
use App\AgentIntegration\Models\AgentCredential;
use App\Cookbook\Models\Store;
use App\FamilyAccess\AuthorizedFamilyContext;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class AgentChangeSetHistoryTest extends TestCase
{
    public function test_content_read_agents_can_list_and_inspect_all_family_change_sets_but_never_delete_history(): void
    {
        [$issuer, $family, $secret, $credential] = $this->credential('Alena', 'Čtecí agent');
        [, , , $otherCredential] = $this->credential('Boris', 'Druhý agent', $family);
        $previewed = AgentChangeSet::factory()->for($family)->for($credential, 'credential')->create([
            'issuer_user_id' => $issuer->id,
            'issuer_name' => $issuer->name,
            'credential_name' => $credential->name,
            'title' => 'Náhled',
            'resource_types' => ['stores'],
        ]);
        $applied = AgentChangeSet::factory()->for($family)->for($otherCredential, 'credential')->create([
            'status' => 'applied',
            'outcome' => 'applied',
            'credential_name' => $otherCredential->name,
            'title' => 'Použitá změna',
            'resource_types' => ['recipes'],
            'warning_acknowledgements' => ['recipe_archive'],
            'identifier_mappings' => ['recipe' => 42],
            'result_document' => ['version' => 1, 'outcome' => 'applied', 'operations' => [], 'resources' => []],
            'applied_at' => now(),
            'terminal_at' => now(),
        ]);
        [, $otherFamily] = $this->memberWithCurrentFamily('Cyril');
        AgentChangeSet::factory()->for($otherFamily)->create(['title' => 'Cizí změna']);

        $this->withToken($secret)->getJson('/api/v1/change-sets')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $applied->id)
            ->assertJsonPath('data.1.id', $previewed->id)
            ->assertJsonMissing(['title' => 'Cizí změna']);
        $this->withToken($secret)->getJson('/api/v1/change-sets?status=applied&resource_type=recipes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $applied->id);
        $this->withToken($secret)->getJson('/api/v1/change-sets/' . $applied->id)
            ->assertOk()
            ->assertJsonPath('data.result.outcome', 'applied')
            ->assertJsonPath('data.credential.name', 'Druhý agent');
        $this->withToken($secret)->getJson('/api/v1/change-sets/' . AgentChangeSet::query()->where('family_id', $otherFamily->id)->sole()->id)
            ->assertNotFound();
        $this->withToken($secret)->deleteJson('/api/v1/change-sets/' . $applied->id)
            ->assertMethodNotAllowed();
        $this->assertModelExists($applied);
    }

    public function test_current_family_members_filter_inspect_and_delete_applied_history_without_reversing_domain_changes(): void
    {
        [$issuer, $family, , $credential] = $this->credential('Alena', 'Kuchyňský agent');
        $member = User::factory()->create(['name' => 'Boris']);
        FamilyMembership::factory()->for($family)->for($member)->create();
        $member->forceFill(['current_family_id' => $family->id])->save();
        $store = Store::factory()->for($family)->create(['name' => 'Trvalý obchod']);
        $applied = AgentChangeSet::factory()->for($family)->for($credential, 'credential')->create([
            'issuer_user_id' => $issuer->id,
            'issuer_name' => 'Alena',
            'credential_name' => 'Kuchyňský agent',
            'status' => 'applied',
            'outcome' => 'applied',
            'title' => 'Doplnění receptu',
            'resource_types' => ['recipes', 'recipe_tags'],
            'canonical_request' => ['version' => 1, 'operations' => [['operation_id' => 'recipe']]],
            'result_document' => ['version' => 1, 'outcome' => 'applied', 'operations' => [], 'resources' => []],
            'applied_at' => now(),
            'terminal_at' => now(),
        ]);
        AgentChangeSet::factory()->for($family)->for($credential, 'credential')->create(['status' => 'previewed']);

        $this->actingAs($member)->get(route('agent-change-sets.index', [
            'credential_id' => $credential->id,
            'issuer_user_id' => $issuer->id,
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
            'resource_type' => 'recipes',
            'outcome' => 'applied',
        ]))->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('agent-change-sets/Index')
            ->has('changeSets', 1)
            ->where('changeSets.0.id', $applied->id)
            ->where('changeSets.0.title', 'Doplnění receptu')
            ->has('credentials', 1)
            ->has('issuers', 1)
            ->where('filters.resourceType', 'recipes'));
        $this->actingAs($member)->get(route('agent-change-sets.show', $applied))
            ->assertOk()->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('agent-change-sets/Show')
            ->where('changeSet.id', $applied->id)
            ->where('changeSet.canonicalRequest.operations.0.operation_id', 'recipe')
            ->where('changeSet.result.outcome', 'applied'));

        $this->actingAs($member)->delete(route('agent-change-sets.destroy', $applied))
            ->assertRedirect(route('agent-change-sets.index'))
            ->assertInertiaFlash('toast.message', 'Historie změny agenta byla smazána. Provedené změny zůstávají beze změny.');
        $this->assertModelMissing($applied);
        $this->assertModelExists($store);
    }

    public function test_web_history_is_current_family_scoped_and_guests_cannot_access_it(): void
    {
        $this->get(route('agent-change-sets.index'))->assertRedirect(route('login'));
        $this->get(route('agent-change-sets.show', '01ARZ3NDEKTSV4RRFFQ69G5FAV'))->assertRedirect(route('login'));
        $this->delete(route('agent-change-sets.destroy', '01ARZ3NDEKTSV4RRFFQ69G5FAV'))->assertRedirect(route('login'));

        [$member, $currentFamily] = $this->memberWithCurrentFamily('Alena');
        $otherFamily = Family::factory()->create();
        FamilyMembership::factory()->for($otherFamily)->for($member)->create();
        $foreign = AgentChangeSet::factory()->for($otherFamily)->create(['status' => 'applied', 'outcome' => 'applied']);

        $this->actingAs($member)->get(route('agent-change-sets.show', $foreign))->assertNotFound();
        $this->actingAs($member)->delete(route('agent-change-sets.destroy', $foreign), ['family_id' => $otherFamily->id])->assertNotFound();
        $this->assertModelExists($foreign);
        $this->assertNotSame($currentFamily->id, $foreign->family_id);
    }

    /** @return array{User, Family, string, AgentCredential} */
    private function credential(string $issuerName, string $credentialName, ?Family $family = null): array
    {
        $issuer = User::factory()->create(['name' => $issuerName]);
        $family ??= Family::factory()->create();
        FamilyMembership::factory()->for($family)->for($issuer)->create();
        $issuer->forceFill(['current_family_id' => $family->id])->save();
        $issued = app(IssueAgentCredential::class)->handle(new AuthorizedFamilyContext($issuer, $family), $credentialName);

        return [$issuer, $family, $issued->plainTextSecret, $issued->credential];
    }

    /** @return array{User, Family} */
    private function memberWithCurrentFamily(string $name): array
    {
        $member = User::factory()->create(['name' => $name]);
        $family = Family::factory()->create();
        FamilyMembership::factory()->for($family)->for($member)->create();
        $member->forceFill(['current_family_id' => $family->id])->save();

        return [$member, $family];
    }
}
