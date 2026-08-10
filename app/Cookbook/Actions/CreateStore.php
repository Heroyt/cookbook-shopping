<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Store;
use App\Cookbook\Values\NormalizedName;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final readonly class CreateStore
{
    public function __construct(private CurrentFamilyScope $currentFamilyScope) {}

    public function handle(User $user, string $name): Store
    {
        return $this->currentFamilyScope->within($user, function (Family $family) use ($name): Store {
            $normalizedName = NormalizedName::from($name);
            $store = Store::query()->createOrFirst(
                [
                    'family_id' => $family->id,
                    'normalized_name' => $normalizedName->key,
                ],
                ['name' => $normalizedName->display],
            );

            if ( ! $store->wasRecentlyCreated) {
                throw ValidationException::withMessages([
                    'name' => __('A Store with this name already exists in the Current Family.'),
                ]);
            }

            return $store;
        });
    }
}
