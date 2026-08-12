<?php

declare(strict_types=1);

namespace App\MealPlanning\Queries;

use App\Cookbook\Models\Recipe;
use App\FamilyAccess\Models\Family;
use App\MealPlanning\Models\CalendarEntry;
use App\MealPlanning\Values\MealLabel;
use App\MealPlanning\Values\ServingCount;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

final class CurrentFamilyCalendarOverview
{
    /** @return array{week: array{startsOn: string, endsOn: string, previousStartsOn: string, nextStartsOn: string}, days: list<array{date: string, weekdayLabel: string, dateLabel: string, entries: list<array{id: int, recipeName: string, mealLabel: string, servingCount: string}>}>} */
    public function project(Family $family, CarbonImmutable $today): array
    {
        $startsOn = $today->startOfWeek();
        $endsOn = $today->endOfWeek();
        $entries = CalendarEntry::query()
            ->select(['id', 'family_id', 'recipe_id', 'date', 'meal_label_key', 'serving_count'])
            ->whereBelongsTo($family)
            ->whereBetween('date', [$startsOn->toDateString(), $endsOn->toDateString()])
            ->with('recipe:id,name')
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
        ];
    }

    /**
     * @param  Collection<int, CalendarEntry>  $entries
     * @return list<array{date: string, weekdayLabel: string, dateLabel: string, entries: list<array{id: int, recipeName: string, mealLabel: string, servingCount: string}>}>
     */
    private function days(CarbonImmutable $startsOn, Collection $entries): array
    {
        $days = [];
        $entriesByDate = $entries->groupBy(fn (CalendarEntry $entry): string => $entry->date->toDateString());

        foreach (range(0, 6) as $offset) {
            $date = $startsOn->addDays($offset);
            $days[] = [
                'date' => $date->toDateString(),
                'weekdayLabel' => $this->weekdayLabel($date->dayOfWeekIso),
                'dateLabel' => $date->format('j. n.'),
                'entries' => $this->entries($entriesByDate->get($date->toDateString(), new Collection())),
            ];
        }

        return $days;
    }

    /**
     * @param  Collection<int, CalendarEntry>  $entries
     * @return list<array{id: int, recipeName: string, mealLabel: string, servingCount: string}>
     */
    private function entries(Collection $entries): array
    {
        $projectedEntries = [];

        foreach ($entries as $entry) {
            $recipe = $entry->recipe;
            if ( ! $recipe instanceof Recipe) {
                continue;
            }

            $projectedEntries[] = [
                'id' => $entry->id,
                'recipeName' => $recipe->name,
                'mealLabel' => MealLabel::displayForKey($entry->meal_label_key),
                'servingCount' => ServingCount::from($entry->serving_count)->toString(),
            ];
        }

        return $projectedEntries;
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
