<?php

declare(strict_types=1);

namespace App\AgentIntegration\Catalog;

enum CatalogResourceType: string
{
    case Stores = 'stores';
    case StoreSections = 'store_sections';
    case Ingredients = 'ingredients';
    case RecipeTags = 'recipe_tags';
    case Recipes = 'recipes';
    case CalendarEntries = 'calendar_entries';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(
            static fn (self $resourceType): string => $resourceType->value,
            self::cases(),
        );
    }
}
