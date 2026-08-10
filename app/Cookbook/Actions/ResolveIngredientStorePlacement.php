<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Store;
use App\Cookbook\Values\IngredientStorePlacement;
use App\FamilyAccess\Models\Family;
use Illuminate\Validation\ValidationException;

final readonly class ResolveIngredientStorePlacement
{
    public function handle(Family $family, ?int $storeId, ?int $storeSectionId): IngredientStorePlacement
    {
        if ($storeSectionId !== null && $storeId === null) {
            throw ValidationException::withMessages([
                'store_id' => __('Select a Store before selecting its Store Section.'),
            ]);
        }

        if ($storeId === null) {
            return new IngredientStorePlacement(null, null);
        }

        $store = Store::query()
            ->whereBelongsTo($family)
            ->whereKey($storeId)
            ->first();

        if ($store === null) {
            throw ValidationException::withMessages([
                'store_id' => __('The selected Store is unavailable in the Current Family.'),
            ]);
        }

        if ($storeSectionId !== null && ! $store->storeSections()->whereKey($storeSectionId)->exists()) {
            throw ValidationException::withMessages([
                'store_section_id' => __('The selected Store Section is not associated with this Store.'),
            ]);
        }

        return new IngredientStorePlacement($store->id, $storeSectionId);
    }
}
