<?php

declare(strict_types=1);

namespace App\MealPlanning\Http\Requests;

use App\Http\Requests\AuthenticatedRequest;

final class CalendarAlternativeStoreRequest extends AuthenticatedRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'original_ingredient_id' => ['required', 'integer', 'min:1'],
            'alternative_ingredient_id' => ['required', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $message = __('The selected Alternative is no longer available for this Ingredient.');

        return [
            'original_ingredient_id.required' => $message,
            'original_ingredient_id.integer' => $message,
            'original_ingredient_id.min' => $message,
            'alternative_ingredient_id.required' => $message,
            'alternative_ingredient_id.integer' => $message,
            'alternative_ingredient_id.min' => $message,
        ];
    }

    public function originalIngredientId(): int
    {
        $value = $this->validated('original_ingredient_id');

        return is_numeric($value) ? (int) $value : 0;
    }

    public function alternativeIngredientId(): int
    {
        $value = $this->validated('alternative_ingredient_id');

        return is_numeric($value) ? (int) $value : 0;
    }
}
