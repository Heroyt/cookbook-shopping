<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\StoreSection;
use App\Cookbook\Values\StoreSectionIcon;
use App\FamilyAccess\AuthorizedFamilyContext;

final readonly class ChangeStoreSectionIcon
{
    public function handle(AuthorizedFamilyContext $context, int $storeSectionId, StoreSectionIcon $icon): StoreSection
    {
        $storeSection = StoreSection::query()
            ->whereBelongsTo($context->family)
            ->whereKey($storeSectionId)
            ->firstOrFail();

        $storeSection->icon = $icon;
        $storeSection->save();

        return $storeSection;
    }
}
