<?php

declare(strict_types=1);

namespace App\AgentIntegration\Media;

use App\Cookbook\Values\EntityMediaType;

enum AgentMediaResourceType: string
{
    case Stores = 'stores';
    case Ingredients = 'ingredients';
    case Recipes = 'recipes';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(
            static fn (self $resourceType): string => $resourceType->value,
            self::cases(),
        );
    }

    /** @return list<string> */
    public static function mediaTypeValues(): array
    {
        return array_map(
            static fn (self $resourceType): string => $resourceType->mediaType()->value,
            self::cases(),
        );
    }

    public function mediaType(): EntityMediaType
    {
        return match ($this) {
            self::Stores => EntityMediaType::StoreLogo,
            self::Ingredients => EntityMediaType::IngredientPhoto,
            self::Recipes => EntityMediaType::RecipeCover,
        };
    }
}
