<?php

declare(strict_types=1);

namespace App\MealPlanning\Http\Requests;

use App\Http\Requests\AuthenticatedRequest;

final class CalendarEntryDestroyRequest extends AuthenticatedRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
