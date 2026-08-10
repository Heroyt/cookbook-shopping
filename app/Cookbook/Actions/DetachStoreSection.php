<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Ingredient;
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
            $storeSection = StoreSection::query()
                ->whereBelongsTo($family)
                ->whereKey($storeSectionId)
                ->lockForUpdate()
                ->firstOrFail();
            $store = Store::query()
                ->whereBelongsTo($family)
                ->whereKey($storeId)
                ->lockForUpdate()
                ->firstOrFail();

            $store->storeSections()->whereKey($storeSection->id)->firstOrFail();
            Ingredient::query()
                ->whereBelongsTo($family)
                ->where('store_id', $store->id)
                ->where('store_section_id', $storeSection->id)
                ->update(['store_section_id' => null]);
            $store->removeStoreSectionAssociation($storeSection);
        });
    }
}
