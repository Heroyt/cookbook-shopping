<?php

declare(strict_types=1);

namespace App\Cookbook\Values;

enum EntityMediaType: string
{
    case StoreLogo = 'store-logo';
    case StoreSectionIcon = 'store-section-icon';
    case IngredientPhoto = 'ingredient-photo';
    case RecipeCover = 'recipe-cover';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(
            static fn (self $type): string => $type->value,
            self::cases(),
        );
    }

    public function managementRoute(): string
    {
        return match ($this) {
            self::StoreLogo, self::StoreSectionIcon => 'stores.index',
            self::IngredientPhoto => 'ingredients.index',
            self::RecipeCover => 'recipes.index',
        };
    }
}
