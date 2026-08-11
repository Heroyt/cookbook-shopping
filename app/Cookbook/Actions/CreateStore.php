<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Store;
use App\Cookbook\Values\NormalizedName;
use App\FamilyAccess\AuthorizedFamilyContext;
use Illuminate\Validation\ValidationException;

final readonly class CreateStore
{
    public function handle(AuthorizedFamilyContext $context, string $name): Store
    {
        $normalizedName = NormalizedName::from($name);
        $store = Store::query()->createOrFirst(
            [
                'family_id' => $context->family->id,
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
    }
}
