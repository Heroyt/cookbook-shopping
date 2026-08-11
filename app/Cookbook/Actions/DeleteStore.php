<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\Store;
use App\Cookbook\Services\EntityMediaStorage;
use App\Cookbook\Values\EntityMediaDeletion;
use App\Cookbook\Values\EntityMediaType;
use App\FamilyAccess\AuthorizedFamilyContext;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class DeleteStore
{
    public function __construct(
        private EntityMediaStorage $entityMediaStorage,
    ) {}

    public function handle(AuthorizedFamilyContext $context, int $storeId): void
    {
        $mediaDeletion = null;

        try {
            DB::transaction(function () use ($context, $storeId, &$mediaDeletion): void {
                $store = Store::query()
                    ->whereBelongsTo($context->family)
                    ->whereKey($storeId)
                    ->lockForUpdate()
                    ->firstOrFail();

                Ingredient::query()
                    ->whereBelongsTo($context->family)
                    ->where('store_id', $store->id)
                    ->update(['store_id' => null, 'store_section_id' => null]);

                $store->delete();
                $mediaDeletion = $this->entityMediaStorage->deleteEntityWithBackup(
                    $context->family->id,
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
