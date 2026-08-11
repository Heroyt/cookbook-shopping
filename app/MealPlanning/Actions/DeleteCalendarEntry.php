<?php

declare(strict_types=1);

namespace App\MealPlanning\Actions;

use App\FamilyAccess\AuthorizedFamilyContext;
use App\MealPlanning\Models\CalendarEntry;

final readonly class DeleteCalendarEntry
{
    public function handle(AuthorizedFamilyContext $context, int $entryId): void
    {
        CalendarEntry::query()
            ->where('family_id', $context->family->id)
            ->whereKey($entryId)
            ->lockForUpdate()
            ->firstOrFail()
            ->delete();
    }
}
