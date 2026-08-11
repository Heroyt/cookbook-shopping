<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Recipe;
use App\FamilyAccess\AuthorizedFamilyContext;

final readonly class ArchiveRecipe
{
    public function handle(AuthorizedFamilyContext $context, int $recipeId): void
    {
        $recipe = Recipe::query()->whereBelongsTo($context->family)->whereKey($recipeId)->lockForUpdate()->firstOrFail();
        $recipe->forceFill(['archived_at' => now()])->save();
    }
}
