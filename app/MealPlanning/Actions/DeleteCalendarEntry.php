<?php

declare(strict_types=1);

namespace App\MealPlanning\Actions;

use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\MealPlanning\Models\CalendarEntry;
use App\Models\User;

final readonly class DeleteCalendarEntry
{
    public function __construct(private CurrentFamilyScope $scope) {}

    public function handle(User $user, int $entryId): void
    {
        $this->scope->within($user, function (Family $family) use ($entryId): void {
            CalendarEntry::query()
                ->where('family_id', $family->id)
                ->whereKey($entryId)
                ->lockForUpdate()
                ->firstOrFail()
                ->delete();
        });
    }
}
