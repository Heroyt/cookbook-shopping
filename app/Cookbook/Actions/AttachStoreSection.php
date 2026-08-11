<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Store;
use App\Cookbook\Models\StoreSection;
use App\FamilyAccess\AuthorizedFamilyContext;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

final readonly class AttachStoreSection
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
        $nextPosition = $store->storeSections()->newPivotQuery()->count();

        try {
            $store->storeSections()->attach($storeSection, ['position' => $nextPosition]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'store_section_id' => __('This Store Section is already associated with the Store.'),
            ]);
        }

        $store->section_order_version++;
        $store->save();
    }
}
