<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\RecipeTag;
use App\FamilyAccess\AuthorizedFamilyContext;

final readonly class DeleteRecipeTag
{
    public function handle(AuthorizedFamilyContext $context, int $tagId): void
    {
        RecipeTag::query()->where('family_id', $context->family->id)->whereKey($tagId)->lockForUpdate()->firstOrFail()->delete();
    }
}
