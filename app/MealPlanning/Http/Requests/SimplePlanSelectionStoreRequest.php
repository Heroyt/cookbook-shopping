<?php

declare(strict_types=1);

namespace App\MealPlanning\Http\Requests;

use App\Http\Requests\AuthenticatedRequest;
use Illuminate\Contracts\Validation\ValidationRule;

final class SimplePlanSelectionStoreRequest extends AuthenticatedRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'recipe_id' => ['required', 'integer', 'min:1'],
            'serving_count' => ['required', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/', 'gt:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'recipe_id.required' => __('Select a Recipe.'),
            'recipe_id.integer' => __('Select a Recipe.'),
            'recipe_id.min' => __('Select a Recipe.'),
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

    public function servingCount(): string
    {
        $servingCount = $this->validated('serving_count');

        return is_string($servingCount) ? $servingCount : '';
    }
}
