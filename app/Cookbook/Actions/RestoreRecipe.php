<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Recipe;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Models\User;

final readonly class RestoreRecipe
{
    public function __construct(private CurrentFamilyScope $scope) {}

    public function handle(User $user, int $recipeId): void
    {
        $this->scope->within($user, function (Family $family) use ($recipeId): void {
            $recipe = Recipe::query()->whereBelongsTo($family)->whereKey($recipeId)->lockForUpdate()->firstOrFail();
            $recipe->forceFill(['archived_at' => null])->save();
        });
    }
}
