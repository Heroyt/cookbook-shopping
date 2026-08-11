<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\RecipeTag;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Models\User;

final readonly class DeleteRecipeTag
{
    public function __construct(private CurrentFamilyScope $scope) {}

    public function handle(User $user, int $tagId): void
    {
        $this->scope->within($user, function (Family $family) use ($tagId): void {
            RecipeTag::query()->where('family_id', $family->id)->whereKey($tagId)->lockForUpdate()->firstOrFail()->delete();
        });
    }
}
