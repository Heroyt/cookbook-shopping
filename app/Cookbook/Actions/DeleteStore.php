<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\Store;
use App\Cookbook\Services\EntityMediaStorage;
use App\Cookbook\Values\EntityMediaDeletion;
use App\Cookbook\Values\EntityMediaType;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Models\User;
use Throwable;

final readonly class DeleteStore
{
    public function __construct(
        private CurrentFamilyScope $currentFamilyScope,
        private EntityMediaStorage $entityMediaStorage,
    ) {}

    public function handle(User $user, int $storeId): void
    {
        $mediaDeletion = null;

        try {
            $this->currentFamilyScope->within($user, function (Family $family) use ($storeId, &$mediaDeletion): void {
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
                $mediaDeletion = $this->entityMediaStorage->deleteEntityWithBackup(
                    $family->id,
                    EntityMediaType::StoreLogo,
                    $storeId,
                );
            }, 1);
        } catch (Throwable $exception) {
            if ($mediaDeletion instanceof EntityMediaDeletion) {
                $this->entityMediaStorage->restore($mediaDeletion);
            }

            throw $exception;
        }
    }
}
