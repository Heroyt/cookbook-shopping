<?php

declare(strict_types=1);

namespace App\MealPlanning\Queries;

use App\Cookbook\Models\Recipe;
use App\Cookbook\Services\RecipeNutritionCalculator;
use App\FamilyAccess\Models\Family;
use App\MealPlanning\Models\CalendarEntry;
use App\MealPlanning\Projectors\CalendarNutritionProjector;
use App\MealPlanning\Values\MealLabel;
use App\MealPlanning\Values\ServingCount;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

final readonly class CurrentFamilyCalendar
{
    public function __construct(
        private RecipeNutritionCalculator $recipeNutritionCalculator,
        private CalendarNutritionProjector $nutritionProjector,
    ) {}

    /** @return array<string, mixed> */
    public function project(Family $family, CarbonImmutable $requestedWeek): array
    {
        $startsOn = $requestedWeek->startOfWeek();
        $endsOn = $startsOn->addDays(6);
        $entries = CalendarEntry::query()
            ->where('family_id', $family->id)
            ->whereBetween('date', [$startsOn->toDateString(), $endsOn->toDateString()])
            ->with('recipe.ingredients.ingredient.nutritionProfile')
            ->orderBy('date')
            ->orderBy('meal_label_key')
            ->orderBy('recipe_id')
            ->get();

        return [
            'week' => [
                'startsOn' => $startsOn->toDateString(),
                'endsOn' => $endsOn->toDateString(),
                'previousStartsOn' => $startsOn->subWeek()->toDateString(),
                'nextStartsOn' => $startsOn->addWeek()->toDateString(),
            ],
            'days' => $this->days($startsOn, $entries),
            'mealLabels' => array_map(
                static fn (MealLabel $label): array => ['value' => $label->value, 'label' => $label->value],
                MealLabel::cases(),
            ),
        ];
    }

    /**
     * @param  Collection<int, CalendarEntry>  $entries
     * @return list<array<string, mixed>>
     */
    private function days(CarbonImmutable $startsOn, Collection $entries): array
    {
        $byDate = $entries->groupBy(fn (CalendarEntry $entry): string => $entry->date->toDateString());
        $days = [];
        foreach (range(0, 6) as $offset) {
            $date = $startsOn->addDays($offset);
            $dateEntries = $byDate->get($date->toDateString(), new Collection());
            $projection = $this->groups($dateEntries);
            $days[] = [
                'date' => $date->toDateString(),
                'weekdayLabel' => $this->weekdayLabel($date->dayOfWeekIso),
                'dateLabel' => $date->format('j. n.'),
                'groups' => $projection['groups'],
                'nutrition' => $this->nutritionProjector->sum($projection['contributions']),
            ];
        }

        return $days;
    }

    /**
     * @param  Collection<int, CalendarEntry>  $entries
     * @return array{
     *     groups: list<array{key: string, label: string, mealLabel: ?string, entries: list<array<string, mixed>>}>,
     *     contributions: list<array{status: string, source: string, totals: array{energyKcal: string, fatGrams: string, proteinGrams: string, carbohydrateGrams: string}, missingIngredientNames: list<string>}>
     * }
     */
    private function groups(Collection $entries): array
    {
        $groups = [];
        $contributions = [];
        foreach ([...array_map(static fn (MealLabel $label): string => $label->key(), MealLabel::cases()), 'unlabeled'] as $key) {
            $projectedEntries = [];
            foreach ($entries->where('meal_label_key', $key) as $entry) {
                $recipe = $entry->recipe;
                if ( ! $recipe instanceof Recipe) {
                    continue;
                }
                $servingCount = ServingCount::from($entry->serving_count)->toString();
                $nutrition = $this->nutritionProjector->scale(
                    $this->recipeNutritionCalculator->calculate($recipe),
                    $servingCount,
                );
                $contributions[] = $nutrition;
                $projectedEntries[] = [
                    'id' => $entry->id,
                    'recipeId' => $recipe->id,
                    'recipeName' => $recipe->name,
                    'recipeArchived' => $recipe->archived_at !== null,
                    'date' => $entry->date->toDateString(),
                    'mealLabel' => MealLabel::nullableFromKey($entry->meal_label_key)?->value,
                    'servingCount' => $servingCount,
                    'nutrition' => $nutrition,
                ];
            }
            $groups[] = [
                'key' => $key,
                'label' => MealLabel::displayForKey($key),
                'mealLabel' => MealLabel::nullableFromKey($key)?->value,
                'entries' => $projectedEntries,
            ];
        }

        return ['groups' => $groups, 'contributions' => $contributions];
    }

    private function weekdayLabel(int $dayOfWeekIso): string
    {
        return match ($dayOfWeekIso) {
            1 => 'pondělí',
            2 => 'úterý',
            3 => 'středa',
            4 => 'čtvrtek',
            5 => 'pátek',
            6 => 'sobota',
            7 => 'neděle',
            default => 'den',
        };
    }
}
