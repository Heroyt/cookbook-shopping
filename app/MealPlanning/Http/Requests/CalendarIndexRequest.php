<?php

declare(strict_types=1);

namespace App\MealPlanning\Http\Requests;

use App\Http\Requests\AuthenticatedRequest;

final class CalendarIndexRequest extends AuthenticatedRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['week' => ['nullable', 'date_format:Y-m-d']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['week.date_format' => __('Enter a date in YYYY-MM-DD format.')];
    }

    public function week(): ?string
    {
        $week = $this->validated('week');

        return is_string($week) ? $week : null;
    }
}
