<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\StoreSection;
use App\Cookbook\Values\NormalizedName;
use App\Cookbook\Values\StoreSectionIcon;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final readonly class CreateStoreSection
{
    public function __construct(private CurrentFamilyScope $currentFamilyScope) {}

    public function handle(User $user, string $name, string $colour, StoreSectionIcon $icon): StoreSection
    {
        return $this->currentFamilyScope->within($user, function (Family $family) use ($name, $colour, $icon): StoreSection {
            $normalizedName = NormalizedName::from($name);
            $storeSection = StoreSection::query()->createOrFirst(
                [
                    'family_id' => $family->id,
                    'normalized_name' => $normalizedName->key,
                ],
                [
                    'name' => $normalizedName->display,
                    'colour' => $colour,
                    'icon' => $icon,
                ],
            );

            if ( ! $storeSection->wasRecentlyCreated) {
                throw ValidationException::withMessages([
                    'name' => __('A Store Section with this name already exists in the Current Family.'),
                ]);
            }

            return $storeSection;
        });
    }
}
