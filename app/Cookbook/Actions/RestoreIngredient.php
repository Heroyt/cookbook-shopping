<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Ingredient;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Models\User;

final readonly class RestoreIngredient
{
    public function __construct(private CurrentFamilyScope $currentFamilyScope) {}

    public function handle(User $user, int $ingredientId): void
    {
        $this->currentFamilyScope->within($user, function (Family $family) use ($ingredientId): void {
            $ingredient = Ingredient::query()
                ->whereBelongsTo($family)
                ->whereKey($ingredientId)
                ->whereNotNull('archived_at')
                ->lockForUpdate()
                ->firstOrFail();

            $ingredient->archived_at = null;
            $ingredient->save();
        });
    }
}
