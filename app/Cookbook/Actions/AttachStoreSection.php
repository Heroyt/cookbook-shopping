<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Store;
use App\Cookbook\Models\StoreSection;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

final readonly class AttachStoreSection
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
        });
    }
}
