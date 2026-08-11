<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\StoreSection;
use App\Cookbook\Values\StoreSectionIcon;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Models\User;

final readonly class ChangeStoreSectionIcon
{
    public function __construct(private CurrentFamilyScope $currentFamilyScope) {}

    public function handle(User $user, int $storeSectionId, StoreSectionIcon $icon): StoreSection
    {
        return $this->currentFamilyScope->within(
            $user,
            function (Family $family) use ($storeSectionId, $icon): StoreSection {
                $storeSection = StoreSection::query()
                    ->whereBelongsTo($family)
                    ->whereKey($storeSectionId)
                    ->firstOrFail();

                $storeSection->icon = $icon;
                $storeSection->save();

                return $storeSection;
            },
        );
    }
}
