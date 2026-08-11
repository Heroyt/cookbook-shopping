<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Store;
use App\FamilyAccess\AuthorizedFamilyContext;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

final readonly class RenameStore
{
    public function handle(AuthorizedFamilyContext $context, int $storeId, string $name): Store
    {
        $store = Store::query()
            ->whereBelongsTo($context->family)
            ->whereKey($storeId)
            ->firstOrFail();

        $store->name = $name;

        try {
            $store->save();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'name' => __('A Store with this name already exists in the Current Family.'),
            ]);
        }

        return $store;
    }
}
