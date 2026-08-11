<?php

declare(strict_types=1);

namespace App\MealPlanning\Queries;

use App\FamilyAccess\Models\Family;
use App\MealPlanning\Models\CalendarEntry;
use App\MealPlanning\Values\GenerationRequestSource;
use App\MealPlanning\Values\ServingCount;
use App\MealPlanning\Values\SimplePlan;
use App\ShoppingGeneration\Values\GenerationRequest;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final readonly class BuildCurrentFamilyCalendarGenerationRequest
{
    public function __construct(private BuildCurrentFamilyGenerationRequest $generationRequest) {}

    /**
     * @param  list<string>  $dates
     * @param  array<int, int>  $alternativeChoices
     */
    public function handle(Family $family, array $dates, array $alternativeChoices = []): GenerationRequest
    {
        $entries = CalendarEntry::query()
            ->where('family_id', $family->id)
            ->whereIn('date', $dates)
            ->orderBy('recipe_id')
            ->get(['recipe_id', 'serving_count']);
        if ($entries->isEmpty()) {
            throw ValidationException::withMessages([
                'dates' => __('Select at least one Calendar date containing a Recipe.'),
            ]);
        }

        $plan = SimplePlan::empty();
        try {
            foreach ($entries as $entry) {
                $plan = $plan->add($entry->recipe_id, ServingCount::from($entry->serving_count));
            }
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages([
                'dates' => __('The selected Calendar dates exceed the supported Serving Count range.'),
            ]);
        }

        return $this->generationRequest->handle(
            $family,
            $plan,
            $alternativeChoices,
            source: GenerationRequestSource::Calendar,
        );
    }
}
