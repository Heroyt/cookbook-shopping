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
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final readonly class UpdateCalendarEntry
{
    public function __construct(private CurrentFamilyScope $scope) {}

    public function handle(
        User $user,
        int $entryId,
        int $recipeId,
        string $date,
        ?MealLabel $mealLabel,
        ServingCount $servingCount,
    ): CalendarEntryWriteResult {
        return $this->scope->within($user, function (Family $family) use ($entryId, $recipeId, $date, $mealLabel, $servingCount): CalendarEntryWriteResult {
            $source = CalendarEntry::query()
                ->where('family_id', $family->id)
                ->whereKey($entryId)
                ->lockForUpdate()
                ->firstOrFail();
            $sourceRecipe = Recipe::query()
                ->where('family_id', $family->id)
                ->whereKey($source->recipe_id)
                ->lockForUpdate()
                ->firstOrFail();
            $mealLabelKey = MealLabel::persistenceKey($mealLabel);
            $identityChanged = $source->recipe_id !== $recipeId
                || $source->date->toDateString() !== $date
                || $source->meal_label_key !== $mealLabelKey;
            if ($sourceRecipe->archived_at !== null && $identityChanged) {
                throw ValidationException::withMessages([
                    'entry' => __('Restore the Recipe before changing the Calendar date, Meal Label, or Recipe.'),
                ]);
            }

            $recipe = Recipe::query()
                ->where('family_id', $family->id)
                ->whereKey($recipeId)
                ->when($sourceRecipe->archived_at === null || $sourceRecipe->id !== $recipeId, fn ($query) => $query->whereNull('archived_at'))
                ->lockForUpdate()
                ->first();
            if ( ! $recipe instanceof Recipe) {
                throw ValidationException::withMessages([
                    'recipe_id' => __('The selected Recipe is unavailable in the Current Family.'),
                ]);
            }

            if ( ! $identityChanged) {
                $source->serving_count = $servingCount->toString();
                $source->save();

                return new CalendarEntryWriteResult($source, false);
            }

            $target = $this->target($family, $source, $recipe->id, $date, $mealLabelKey);
            if ($target instanceof CalendarEntry) {
                return $this->merge($source, $target, $servingCount);
            }

            try {
                $source->forceFill([
                    'recipe_id' => $recipe->id,
                    'date' => $date,
                    'meal_label_key' => $mealLabelKey,
                    'serving_count' => $servingCount->toString(),
                ])->save();

                return new CalendarEntryWriteResult($source, false);
            } catch (UniqueConstraintViolationException) {
                $target = $this->target($family, $source, $recipe->id, $date, $mealLabelKey);
                if ( ! $target instanceof CalendarEntry) {
                    throw ValidationException::withMessages([
                        'entry' => __('The Calendar changed. Review the current week and try again.'),
                    ]);
                }

                return $this->merge($source, $target, $servingCount);
            }
        });
    }

    private function target(Family $family, CalendarEntry $source, int $recipeId, string $date, string $mealLabelKey): ?CalendarEntry
    {
        return CalendarEntry::query()
            ->where('family_id', $family->id)
            ->where('recipe_id', $recipeId)
            ->where('date', $date)
            ->where('meal_label_key', $mealLabelKey)
            ->whereKeyNot($source->id)
            ->lockForUpdate()
            ->first();
    }

    private function merge(CalendarEntry $source, CalendarEntry $target, ServingCount $submitted): CalendarEntryWriteResult
    {
        try {
            $target->serving_count = ServingCount::from($target->serving_count)->plus($submitted)->toString();
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages([
                'serving_count' => __('The resulting Serving Count is outside the supported range.'),
            ]);
        }
        $target->save();
        CalendarEntry::query()->whereKey($source->id)->delete();

        return new CalendarEntryWriteResult($target, true);
    }
}
