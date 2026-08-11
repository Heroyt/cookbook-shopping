<?php

declare(strict_types=1);

namespace App\MealPlanning\Actions;

use App\Cookbook\Models\Recipe;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\MealPlanning\Models\CalendarEntry;
use App\MealPlanning\Values\CalendarEntryWriteResult;
use App\MealPlanning\Values\MealLabel;
use App\MealPlanning\Values\ServingCount;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final readonly class CreateCalendarEntry
{
    public function __construct(private CurrentFamilyScope $scope) {}

    public function handle(
        User $user,
        int $recipeId,
        string $date,
        ?MealLabel $mealLabel,
        ServingCount $servingCount,
    ): CalendarEntryWriteResult {
        return $this->scope->within($user, function (Family $family) use ($recipeId, $date, $mealLabel, $servingCount): CalendarEntryWriteResult {
            $recipe = Recipe::query()
                ->whereBelongsTo($family)
                ->whereKey($recipeId)
                ->whereNull('archived_at')
                ->lockForUpdate()
                ->first();
            if ( ! $recipe instanceof Recipe) {
                throw ValidationException::withMessages([
                    'recipe_id' => __('The selected Recipe is unavailable in the Current Family.'),
                ]);
            }

            $identity = [
                'family_id' => $family->id,
                'recipe_id' => $recipe->id,
                'date' => $date,
                'meal_label_key' => MealLabel::persistenceKey($mealLabel),
            ];
            $entry = CalendarEntry::query()
                ->where('family_id', $family->id)
                ->where('recipe_id', $recipe->id)
                ->where('date', $date)
                ->where('meal_label_key', $identity['meal_label_key'])
                ->lockForUpdate()
                ->first();
            if ( ! $entry instanceof CalendarEntry) {
                $entry = CalendarEntry::query()->create([
                    ...$identity,
                    'serving_count' => $servingCount->toString(),
                ]);

                return new CalendarEntryWriteResult($entry, false);
            }
            try {
                $entry->serving_count = ServingCount::from($entry->serving_count)->plus($servingCount)->toString();
            } catch (InvalidArgumentException) {
                throw ValidationException::withMessages([
                    'serving_count' => __('The resulting Serving Count is outside the supported range.'),
                ]);
            }
            $entry->save();

            return new CalendarEntryWriteResult($entry, true);
        });
    }
}
