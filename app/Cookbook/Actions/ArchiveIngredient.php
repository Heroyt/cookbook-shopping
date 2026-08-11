<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Ingredient;
use App\FamilyAccess\AuthorizedFamilyContext;
use Illuminate\Support\Carbon;

final readonly class ArchiveIngredient
{
    public function handle(AuthorizedFamilyContext $context, int $ingredientId): void
    {
        $ingredient = Ingredient::query()
            ->whereBelongsTo($context->family)
            ->whereKey($ingredientId)
            ->whereNull('archived_at')
            ->lockForUpdate()
            ->firstOrFail();

        $ingredient->archived_at = Carbon::now();
        $ingredient->save();
    }
}
