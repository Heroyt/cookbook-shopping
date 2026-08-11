<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\StoreSection;
use App\FamilyAccess\AuthorizedFamilyContext;

final readonly class ChangeStoreSectionColour
{
    public function handle(AuthorizedFamilyContext $context, int $storeSectionId, string $colour): StoreSection
    {
        $storeSection = StoreSection::query()
            ->whereBelongsTo($context->family)
            ->whereKey($storeSectionId)
            ->firstOrFail();

        $storeSection->colour = $colour;
        $storeSection->save();

        return $storeSection;
    }
}
