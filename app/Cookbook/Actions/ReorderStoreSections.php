<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Store;
use App\Cookbook\Models\StoreSection;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final readonly class ReorderStoreSections
{
    public function __construct(private CurrentFamilyScope $currentFamilyScope) {}

    /** @param list<int> $storeSectionIds */
    public function handle(User $user, int $storeId, array $storeSectionIds, int $version): void
    {
        $this->currentFamilyScope->within(
            $user,
            function (Family $family) use ($storeId, $storeSectionIds, $version): void {
                $store = Store::query()
                    ->whereBelongsTo($family)
                    ->whereKey($storeId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($store->section_order_version !== $version) {
                    throw ValidationException::withMessages([
                        'version' => __('The Store Section order changed. Review the fresh order and try again.'),
                    ]);
                }

                $currentIds = $store->storeSections()
                    ->get(['store_sections.id'])
                    ->map(fn (StoreSection $storeSection): int => $storeSection->id)
                    ->all();

                $expectedIds = $currentIds;
                $submittedIds = $storeSectionIds;
                sort($expectedIds, SORT_NUMERIC);
                sort($submittedIds, SORT_NUMERIC);

                if ($submittedIds !== $expectedIds || count(array_unique($storeSectionIds)) !== count($storeSectionIds)) {
                    throw ValidationException::withMessages([
                        'store_section_ids' => __('The order must contain every associated Store Section exactly once.'),
                    ]);
                }

                if ($storeSectionIds !== []) {
                    $store->storeSections()->newPivotQuery()->increment('position', count($storeSectionIds));

                    foreach ($storeSectionIds as $position => $storeSectionId) {
                        $store->storeSections()->updateExistingPivot($storeSectionId, ['position' => $position]);
                    }
                }

                $store->section_order_version++;
                $store->save();
            },
        );
    }
}
