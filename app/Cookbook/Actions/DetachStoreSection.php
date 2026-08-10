<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Store;
use App\Cookbook\Models\StoreSection;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Models\User;

final readonly class DetachStoreSection
{
    public function __construct(private CurrentFamilyScope $currentFamilyScope) {}

    public function handle(User $user, int $storeId, int $storeSectionId): void
    {
        $this->currentFamilyScope->within($user, function (Family $family) use ($storeId, $storeSectionId): void {
            $store = Store::query()
                ->whereBelongsTo($family)
                ->whereKey($storeId)
                ->lockForUpdate()
                ->firstOrFail();
            $storeSection = StoreSection::query()
                ->whereBelongsTo($family)
                ->whereKey($storeSectionId)
                ->firstOrFail();

            $store->storeSections()->whereKey($storeSection->id)->firstOrFail();
            $store->storeSections()->detach($storeSection);

            $store->storeSections->each(function (StoreSection $remainingSection, int $position) use ($store): void {
                $store->storeSections()->updateExistingPivot($remainingSection->id, ['position' => $position]);
            });

            $store->section_order_version++;
            $store->save();
        });
    }
}
