<?php

declare(strict_types=1);

namespace App\MealPlanning\Http\Requests;

use App\Http\Requests\AuthenticatedRequest;

final class CalendarGenerateRequest extends AuthenticatedRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'dates' => ['required', 'array', 'min:1'],
            'dates.*' => ['required', 'date_format:Y-m-d', 'distinct:strict'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'dates.required' => __('Select at least one Calendar date containing a Recipe.'),
            'dates.array' => __('Select at least one Calendar date containing a Recipe.'),
            'dates.min' => __('Select at least one Calendar date containing a Recipe.'),
            'dates.*.date_format' => __('Enter a date in YYYY-MM-DD format.'),
            'dates.*.distinct' => __('Select each Calendar date only once.'),
        ];
    }

    /** @return list<string> */
    public function dates(): array
    {
        $dates = $this->validated('dates');
        if ( ! is_array($dates)) {
            return [];
        }

        $result = array_values(array_filter($dates, 'is_string'));
        sort($result);

        return $result;
    }
}
