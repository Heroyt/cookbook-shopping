<?php

declare(strict_types=1);

namespace App\MealPlanning\Http\Requests;

use App\Http\Requests\AuthenticatedRequest;
use App\MealPlanning\Values\MealLabel;
use App\MealPlanning\Values\ServingCount;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class CalendarEntryWriteRequest extends AuthenticatedRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'recipe_id' => ['required', 'integer', 'min:1'],
            'date' => ['required', 'date_format:Y-m-d'],
            'meal_label' => ['nullable', Rule::enum(MealLabel::class)],
            'serving_count' => ['required', 'regex:/^(?:0|[1-9]\d{0,13})(?:\.\d{1,6})?$/', 'gt:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'recipe_id.required' => __('Select a Recipe.'),
            'date.required' => __('Enter a Calendar date.'),
            'date.date_format' => __('Enter a date in YYYY-MM-DD format.'),
            'meal_label.enum' => __('Select a valid Meal Label.'),
            'serving_count.required' => __('Enter a Serving Count.'),
            'serving_count.regex' => __('The Serving Count must be a positive decimal with at most six fractional places.'),
            'serving_count.gt' => __('The Serving Count must be a positive decimal with at most six fractional places.'),
        ];
    }

    public function recipeId(): int
    {
        $recipeId = $this->validated('recipe_id');

        return is_numeric($recipeId) ? (int) $recipeId : 0;
    }

    public function calendarDate(): string
    {
        $date = $this->validated('date');

        return is_string($date) ? $date : '';
    }

    public function mealLabel(): ?MealLabel
    {
        $label = $this->validated('meal_label');

        return is_string($label) ? MealLabel::from($label) : null;
    }

    public function servingCount(): ServingCount
    {
        $value = $this->validated('serving_count');

        return ServingCount::from(is_string($value) ? $value : '');
    }
}
