<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\StoreSection;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Models\User;

final readonly class DeleteStoreSection
{
    public function __construct(private CurrentFamilyScope $currentFamilyScope) {}

    public function handle(User $user, int $storeSectionId): void
    {
        $this->currentFamilyScope->within($user, function (Family $family) use ($storeSectionId): void {
            $storeSection = StoreSection::query()
                ->whereBelongsTo($family)
                ->whereKey($storeSectionId)
                ->lockForUpdate()
                ->firstOrFail();
            $affectedStores = $storeSection->stores()
                ->whereBelongsTo($family)
                ->orderBy('stores.id')
                ->lockForUpdate()
                ->get();

            foreach ($affectedStores as $store) {
                $store->removeStoreSectionAssociation($storeSection);
            }

            $storeSection->delete();
        });
    }
}
