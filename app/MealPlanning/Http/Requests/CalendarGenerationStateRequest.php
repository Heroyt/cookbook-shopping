<?php

declare(strict_types=1);

namespace App\MealPlanning\Http\Requests;

use App\Http\Requests\AuthenticatedRequest;

final class CalendarGenerationStateRequest extends AuthenticatedRequest
{
    /** @return array<string, never> */
    public function rules(): array
    {
        return [];
    }
}
