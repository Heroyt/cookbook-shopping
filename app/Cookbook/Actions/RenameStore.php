<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Store;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

final readonly class RenameStore
{
    public function __construct(private CurrentFamilyScope $currentFamilyScope) {}

    public function handle(User $user, int $storeId, string $name): Store
    {
        return $this->currentFamilyScope->within($user, function (Family $family) use ($storeId, $name): Store {
            $store = Store::query()
                ->whereBelongsTo($family)
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
        });
    }
}
