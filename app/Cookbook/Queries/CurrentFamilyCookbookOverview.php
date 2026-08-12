<?php

declare(strict_types=1);

namespace App\Cookbook\Queries;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\Store;
use App\FamilyAccess\Models\Family;

final class CurrentFamilyCookbookOverview
{
    /** @return array{recipeCount: int, ingredientCount: int, storeCount: int} */
    public function counts(Family $family): array
    {
        return [
            'recipeCount' => Recipe::query()->whereBelongsTo($family)->whereNull('archived_at')->count(),
            'ingredientCount' => Ingredient::query()->whereBelongsTo($family)->whereNull('archived_at')->count(),
            'storeCount' => Store::query()->whereBelongsTo($family)->count(),
        ];
    }
}
