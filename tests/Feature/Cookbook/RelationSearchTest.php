<?php

declare(strict_types=1);

namespace Tests\Feature\Cookbook;

use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\Store;
use App\Cookbook\Models\StoreSection;
use App\Cookbook\Values\StoreSectionIcon;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Tests\TestCase;

final class RelationSearchTest extends TestCase
{
    public function test_guests_cannot_search_family_relations(): void
    {
        $this->getJson(route('relation-search.recipes'))->assertUnauthorized();
        $this->getJson(route('relation-search.stores'))->assertUnauthorized();
        $this->getJson(route('relation-search.store-sections', ['store_id' => 1]))->assertUnauthorized();
    }

    public function test_recipe_search_is_current_family_scoped_searchable_and_cursor_paginated(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($user, $family);

        foreach (range(1, 22) as $position) {
            Recipe::factory()->for($family)->create([
                'name' => sprintf('Polévka %02d', $position),
            ]);
        }

        Recipe::factory()->for($family)->create(['name' => 'Koláč']);
        Recipe::factory()->for($family)->create(['name' => 'Polévka archivní', 'archived_at' => now()]);
        Recipe::factory()->for($otherFamily)->create(['name' => 'Polévka cizí']);

        $firstPage = $this->actingAs($user)->getJson(route('relation-search.recipes'));

        $firstPage
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('data.0.name', 'Koláč')
            ->assertJsonStructure(['data' => [['id', 'name']], 'nextCursor']);

        $cursor = $firstPage->json('nextCursor');
        $this->assertIsString($cursor);

        $this->actingAs($user)
            ->getJson(route('relation-search.recipes', ['cursor' => $cursor]))
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('nextCursor', null)
            ->assertJsonMissing(['name' => 'Polévka archivní'])
            ->assertJsonMissing(['name' => 'Polévka cizí']);

        $this->actingAs($user)
            ->getJson(route('relation-search.recipes', ['q' => '  POLÉVKA 02  ']))
            ->assertOk()
            ->assertJson([
                'data' => [['name' => 'Polévka 02']],
                'nextCursor' => null,
            ]);
    }

    public function test_store_search_uses_the_shared_limit_contract_and_excludes_other_families(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($user, $family);

        Store::factory()->for($family)->create(['name' => 'Albert']);
        Store::factory()->for($family)->create(['name' => 'Lidl']);
        Store::factory()->for($otherFamily)->create(['name' => 'Cizí Lidl']);

        $this->actingAs($user)
            ->getJson(route('relation-search.stores', ['q' => 'li', 'limit' => 1]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Lidl')
            ->assertJsonStructure(['data' => [['id', 'name', 'logoUrl']], 'nextCursor']);

        $this->actingAs($user)
            ->getJson(route('relation-search.stores', ['limit' => 101]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('limit');
    }

    public function test_store_section_search_requires_a_current_family_store_and_returns_only_associated_sections(): void
    {
        $user = User::factory()->create();
        $family = $this->createFamilyWithMembers($user);
        $otherFamily = $this->createFamilyWithMembers(User::factory()->create());
        $this->selectCurrentFamily($user, $family);

        $store = Store::factory()->for($family)->create();
        $otherStore = Store::factory()->for($family)->create();
        $produce = StoreSection::factory()->for($family)->create([
            'name' => 'Ovoce a zelenina',
            'colour' => '#16a34a',
            'icon' => StoreSectionIcon::Apple,
        ]);
        $bakery = StoreSection::factory()->for($family)->create(['name' => 'Pečivo']);
        $foreign = StoreSection::factory()->for($otherFamily)->create(['name' => 'Cizí sekce']);
        $store->storeSections()->attach($produce->id, ['position' => 0]);
        $otherStore->storeSections()->attach($bakery->id, ['position' => 0]);

        $this->actingAs($user)
            ->getJson(route('relation-search.store-sections', ['store_id' => $store->id]))
            ->assertOk()
            ->assertJson([
                'data' => [[
                    'id' => $produce->id,
                    'name' => 'Ovoce a zelenina',
                    'colour' => '#16a34a',
                    'icon' => StoreSectionIcon::Apple->value,
                    'iconUrl' => null,
                ]],
                'nextCursor' => null,
            ])
            ->assertJsonMissing(['id' => $bakery->id])
            ->assertJsonMissing(['id' => $foreign->id]);

        $this->actingAs($user)
            ->getJson(route('relation-search.store-sections'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('store_id');

        $foreignStore = Store::factory()->for($otherFamily)->create();
        $this->actingAs($user)
            ->getJson(route('relation-search.store-sections', ['store_id' => $foreignStore->id]))
            ->assertNotFound();
    }

    private function createFamilyWithMembers(User ...$members): Family
    {
        $family = Family::factory()->create();

        foreach ($members as $member) {
            FamilyMembership::factory()->for($family)->for($member)->create();
        }

        return $family;
    }

    private function selectCurrentFamily(User $user, Family $family): void
    {
        $user->forceFill(['current_family_id' => $family->id])->save();
    }
}
