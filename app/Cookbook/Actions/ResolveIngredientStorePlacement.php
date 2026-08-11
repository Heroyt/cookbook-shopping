<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Store;
use App\Cookbook\Values\IngredientStorePlacement;
use App\FamilyAccess\Models\Family;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
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
            ->lockForUpdate()
            ->first();

        if ($store === null) {
            throw ValidationException::withMessages([
                'store_id' => __('The selected Store is unavailable in the Current Family.'),
            ]);
        }

        if ($storeSectionId !== null && ! $this->associatedSectionExists($store->id, $storeSectionId, lock: true)) {
            throw ValidationException::withMessages([
                'store_section_id' => __('The selected Store Section is not associated with this Store.'),
            ]);
        }

        return new IngredientStorePlacement($store->id, $storeSectionId);
    }

    public function rethrowAsValidationExceptionIfUnavailable(
        Family $family,
        IngredientStorePlacement $placement,
        QueryException $exception,
    ): never {
        if ($placement->storeId !== null && ! Store::query()->whereBelongsTo($family)->whereKey($placement->storeId)->exists()) {
            throw ValidationException::withMessages([
                'store_id' => __('The selected Store is no longer available in the Current Family.'),
            ]);
        }

        if ($placement->storeId !== null
            && $placement->storeSectionId !== null
            && ! $this->associatedSectionExists($placement->storeId, $placement->storeSectionId)) {
            throw ValidationException::withMessages([
                'store_section_id' => __('The selected Store Section is no longer associated with this Store.'),
            ]);
        }

        throw $exception;
    }

    private function associatedSectionExists(int $storeId, int $storeSectionId, bool $lock = false): bool
    {
        return DB::table('store_store_section')
            ->where('store_id', $storeId)
            ->where('store_section_id', $storeSectionId)
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->exists();
    }
}
