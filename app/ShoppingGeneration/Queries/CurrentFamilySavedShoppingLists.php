<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Queries;

use App\FamilyAccess\Models\Family;
use App\ShoppingGeneration\Models\SavedShoppingList;
use Illuminate\Pagination\CursorPaginator;

final class CurrentFamilySavedShoppingLists
{
    public function latest(Family $family): ?SavedShoppingList
    {
        return SavedShoppingList::query()
            ->select([
                'id',
                'family_id',
                'generated_at',
                'source_kind',
                'payload_schema_version',
            ])
            ->whereBelongsTo($family)
            ->orderByDesc('generated_at')
            ->orderByDesc('id')
            ->first();
    }

    /** @return CursorPaginator<int, SavedShoppingList> */
    public function page(Family $family): CursorPaginator
    {
        return SavedShoppingList::query()
            ->select([
                'id',
                'family_id',
                'generated_at',
                'source_kind',
                'payload_schema_version',
            ])
            ->whereBelongsTo($family)
            ->orderByDesc('generated_at')
            ->orderByDesc('id')
            ->cursorPaginate(24);
    }

    public function find(Family $family, int $snapshotId): SavedShoppingList
    {
        return SavedShoppingList::query()
            ->whereBelongsTo($family)
            ->whereKey($snapshotId)
            ->firstOrFail();
    }
}
