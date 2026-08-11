<?php

declare(strict_types=1);

namespace App\MealPlanning\Values;

use App\MealPlanning\Models\CalendarEntry;

final readonly class CalendarEntryWriteResult
{
    public function __construct(public CalendarEntry $entry, public bool $merged) {}
}
