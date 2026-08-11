<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\StoreSection;
use App\Cookbook\Services\EntityMediaStorage;
use App\Cookbook\Values\EntityMediaDeletion;
use App\Cookbook\Values\EntityMediaType;
use App\FamilyAccess\AuthorizedFamilyContext;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class DeleteStoreSection
{
    public function __construct(
        private EntityMediaStorage $entityMediaStorage,
    ) {}

    public function handle(AuthorizedFamilyContext $context, int $storeSectionId): void
    {
        $mediaDeletion = null;

        try {
            DB::transaction(function () use ($context, $storeSectionId, &$mediaDeletion): void {
                $storeSection = StoreSection::query()
                    ->whereBelongsTo($context->family)
                    ->whereKey($storeSectionId)
                    ->lockForUpdate()
                    ->firstOrFail();
                $affectedStores = $storeSection->stores()
                    ->whereBelongsTo($context->family)
                    ->orderBy('stores.id')
                    ->lockForUpdate()
                    ->get();

                foreach ($affectedStores as $store) {
                    Ingredient::query()
                        ->whereBelongsTo($context->family)
                        ->where('store_id', $store->id)
                        ->where('store_section_id', $storeSection->id)
                        ->update(['store_section_id' => null]);
                    $store->removeStoreSectionAssociation($storeSection);
                }

                $storeSection->delete();
                $mediaDeletion = $this->entityMediaStorage->deleteEntityWithBackup(
                    $context->family->id,
                    EntityMediaType::StoreSectionIcon,
                    $storeSectionId,
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
