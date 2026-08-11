<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Ingredient;
use App\FamilyAccess\AuthorizedFamilyContext;

final readonly class RestoreIngredient
{
    public function handle(AuthorizedFamilyContext $context, int $ingredientId): void
    {
        $ingredient = Ingredient::query()
            ->whereBelongsTo($context->family)
            ->whereKey($ingredientId)
            ->whereNotNull('archived_at')
            ->lockForUpdate()
            ->firstOrFail();

        $ingredient->archived_at = null;
        $ingredient->save();
    }
}
