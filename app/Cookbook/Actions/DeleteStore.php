<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\Store;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Models\User;

final readonly class DeleteStore
{
    public function __construct(private CurrentFamilyScope $currentFamilyScope) {}

    public function handle(User $user, int $storeId): void
    {
        $this->currentFamilyScope->within($user, function (Family $family) use ($storeId): void {
            $store = Store::query()
                ->whereBelongsTo($family)
                ->whereKey($storeId)
                ->lockForUpdate()
                ->firstOrFail();

            Ingredient::query()
                ->whereBelongsTo($family)
                ->where('store_id', $store->id)
                ->update(['store_id' => null, 'store_section_id' => null]);

            $store->delete();
        });
    }
}
