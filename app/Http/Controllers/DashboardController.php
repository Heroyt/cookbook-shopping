<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Cookbook\Queries\CurrentFamilyCookbookOverview;
use App\FamilyAccess\CurrentFamily;
use App\FamilyAccess\Models\Family;
use App\Http\Requests\DashboardRequest;
use App\MealPlanning\Queries\CurrentFamilyCalendarOverview;
use App\MealPlanning\Queries\CurrentFamilySimplePlan;
use App\MealPlanning\Session\SimplePlanSession;
use App\ShoppingGeneration\Models\SavedShoppingList;
use App\ShoppingGeneration\Queries\CurrentFamilySavedShoppingLists;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly CurrentFamily $currentFamily,
        private readonly CurrentFamilyCalendarOverview $calendar,
        private readonly SimplePlanSession $simplePlanSession,
        private readonly CurrentFamilySimplePlan $simplePlan,
        private readonly CurrentFamilySavedShoppingLists $shoppingLists,
        private readonly CurrentFamilyCookbookOverview $cookbook,
    ) {}

    public function __invoke(DashboardRequest $request): Response
    {
        $family = $this->currentFamily->resolve($request->authenticatedUser());

        return Inertia::render('Dashboard', [
            'overview' => $family instanceof Family ? $this->overview($request, $family) : null,
        ]);
    }

    /** @return array<string, mixed> */
    private function overview(DashboardRequest $request, Family $family): array
    {
        $today = CarbonImmutable::today();
        $calendar = $this->calendar->project($family, $today);
        $simplePlan = $this->simplePlanSession->load($request->session(), $family->id);
        $simplePlanProjection = $this->simplePlan->project($family, $simplePlan);

        return [
            'familyName' => $family->name,
            'today' => $today->toDateString(),
            'week' => $calendar['week'],
            'days' => $calendar['days'],
            'simplePlanSelections' => $simplePlanProjection['selections'],
            'latestShoppingList' => $this->shoppingListSummary($this->shoppingLists->latest($family)),
            'setup' => $this->cookbook->counts($family),
        ];
    }

    /** @return array{id: int, generatedAt: string, sourceKind: 'simple_plan'|'calendar', sourceLabel: string}|null */
    private function shoppingListSummary(?SavedShoppingList $shoppingList): ?array
    {
        if ( ! $shoppingList instanceof SavedShoppingList) {
            return null;
        }

        return [
            'id' => $shoppingList->id,
            'generatedAt' => $shoppingList->generated_at->format('j. n. Y H:i:s') . ',' . $shoppingList->generated_at->format('u'),
            'sourceKind' => $shoppingList->source_kind->value,
            'sourceLabel' => $shoppingList->source_kind->label(),
        ];
    }
}
