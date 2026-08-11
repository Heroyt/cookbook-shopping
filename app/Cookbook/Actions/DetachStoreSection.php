<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\Store;
use App\Cookbook\Models\StoreSection;
use App\FamilyAccess\AuthorizedFamilyContext;
use Illuminate\Support\Facades\DB;

final readonly class DetachStoreSection
{
    public function handle(AuthorizedFamilyContext $context, int $storeId, int $storeSectionId): void
    {
        $storeSection = StoreSection::query()
            ->whereBelongsTo($context->family)
            ->whereKey($storeSectionId)
            ->lockForUpdate()
            ->firstOrFail();
        $store = Store::query()
            ->whereBelongsTo($context->family)
            ->whereKey($storeId)
            ->lockForUpdate()
            ->firstOrFail();

        abort_unless(
            DB::table('store_store_section')
                ->where('store_id', $store->id)
                ->where('store_section_id', $storeSection->id)
                ->lockForUpdate()
                ->exists(),
            404,
        );
        Ingredient::query()
            ->whereBelongsTo($context->family)
            ->where('store_id', $store->id)
            ->where('store_section_id', $storeSection->id)
            ->update(['store_section_id' => null]);
        $store->removeStoreSectionAssociation($storeSection);
    }
}
