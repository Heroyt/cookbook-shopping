<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\Store;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\MealPlanning\Models\CalendarEntry;
use App\Models\User;
use App\ShoppingGeneration\Models\SavedShoppingList;
use App\ShoppingGeneration\Values\SavedShoppingListSource;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class DashboardTest extends TestCase
{
    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_without_a_family_see_the_family_empty_state(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response->assertOk()->assertInertia(fn (Assert $page): Assert => $page
            ->component('Dashboard')
            ->where('overview', null));
    }

    public function test_dashboard_projects_only_the_current_family_workflows(): void
    {
        CarbonImmutable::setTestNow('2026-08-12 09:30:00');
        $user = User::factory()->create();
        $currentFamily = Family::factory()->create();
        $otherFamily = Family::factory()->create();
        FamilyMembership::factory()->for($currentFamily)->for($user)->create();
        FamilyMembership::factory()->for($otherFamily)->for($user)->create();
        $user->forceFill(['current_family_id' => $currentFamily->id])->save();

        $recipe = Recipe::factory()->for($currentFamily)->create(['name' => 'Rajčatová polévka']);
        $otherRecipe = Recipe::factory()->for($otherFamily)->create(['name' => 'Cizí recept']);
        Ingredient::factory()->for($currentFamily)->create();
        Ingredient::factory()->for($otherFamily)->create();
        Store::factory()->for($currentFamily)->create();
        Store::factory()->for($otherFamily)->create();
        CalendarEntry::factory()->for($currentFamily)->for($recipe)->create([
            'date' => '2026-08-12',
            'meal_label_key' => 'lunch',
            'serving_count' => '2.5',
        ]);
        CalendarEntry::factory()->for($otherFamily)->for($otherRecipe)->create([
            'date' => '2026-08-12',
            'meal_label_key' => 'dinner',
        ]);
        SavedShoppingList::factory()->for($currentFamily)->create([
            'generated_at' => '2026-08-11 08:00:00.000001',
        ]);
        $latestSnapshot = SavedShoppingList::factory()->for($currentFamily)->create([
            'generated_at' => '2026-08-12 08:15:30.123456',
            'source_kind' => SavedShoppingListSource::Calendar,
        ]);
        SavedShoppingList::factory()->for($otherFamily)->create([
            'generated_at' => '2026-08-12 09:00:00.000001',
        ]);

        $this->actingAs($user)->withSession([
            "meal_planning.simple_plan.{$currentFamily->id}" => [(string) $recipe->id => '3.5'],
            "meal_planning.simple_plan.{$otherFamily->id}" => [(string) $otherRecipe->id => '8'],
        ])->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Dashboard')
                ->where('overview.familyName', $currentFamily->name)
                ->where('overview.today', '2026-08-12')
                ->where('overview.week.startsOn', '2026-08-10')
                ->has('overview.days', 7)
                ->where('overview.days.2.entries.0.recipeName', 'Rajčatová polévka')
                ->where('overview.days.2.entries.0.mealLabel', 'oběd')
                ->where('overview.days.2.entries.0.servingCount', '2.5')
                ->has('overview.days.3.entries', 0)
                ->has('overview.simplePlanSelections', 1)
                ->where('overview.simplePlanSelections.0.recipeName', 'Rajčatová polévka')
                ->where('overview.simplePlanSelections.0.servingCount', '3.5')
                ->where('overview.latestShoppingList.id', $latestSnapshot->id)
                ->where('overview.latestShoppingList.generatedAt', '12. 8. 2026 08:15:30,123456')
                ->where('overview.latestShoppingList.sourceKind', 'calendar')
                ->where('overview.latestShoppingList.sourceLabel', 'Kalendář')
                ->where('overview.setup', [
                    'recipeCount' => 1,
                    'ingredientCount' => 1,
                    'storeCount' => 1,
                ]));
    }
}
