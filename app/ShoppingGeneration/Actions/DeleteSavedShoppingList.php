<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Actions;

use App\FamilyAccess\Models\Family;
use App\ShoppingGeneration\Models\SavedShoppingList;

final class DeleteSavedShoppingList
{
    public function handle(Family $family, int $snapshotId): void
    {
        SavedShoppingList::query()
            ->whereBelongsTo($family)
            ->whereKey($snapshotId)
            ->firstOrFail(['id'])
            ->delete();
    }
}
