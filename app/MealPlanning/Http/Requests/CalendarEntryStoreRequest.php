<?php

declare(strict_types=1);

namespace App\MealPlanning\Http\Requests;

final class CalendarEntryStoreRequest extends CalendarEntryWriteRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [...parent::rules(), 'repeat_days' => ['nullable', 'integer', 'min:1', 'max:31']];
    }

    public function repeatDays(): int
    {
        $repeatDays = $this->validated('repeat_days');

        return is_numeric($repeatDays) ? (int) $repeatDays : 1;
    }
}
