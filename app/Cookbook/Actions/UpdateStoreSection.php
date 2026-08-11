<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\StoreSection;
use App\Cookbook\Values\StoreSectionIcon;
use App\FamilyAccess\AuthorizedFamilyContext;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

final readonly class UpdateStoreSection
{
    public function handle(
        AuthorizedFamilyContext $context,
        int $storeSectionId,
        string $name,
        string $colour,
        StoreSectionIcon $icon,
    ): StoreSection {
        $storeSection = StoreSection::query()
            ->whereBelongsTo($context->family)
            ->whereKey($storeSectionId)
            ->lockForUpdate()
            ->firstOrFail();
        $storeSection->fill(['name' => $name, 'colour' => $colour, 'icon' => $icon]);

        try {
            $storeSection->save();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'name' => __('A Store Section with this name already exists in the Current Family.'),
            ]);
        }

        return $storeSection;
    }
}
