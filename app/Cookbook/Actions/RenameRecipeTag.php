<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\RecipeTag;
use App\FamilyAccess\AuthorizedFamilyContext;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

final readonly class RenameRecipeTag
{
    public function handle(AuthorizedFamilyContext $context, int $recipeTagId, string $name): RecipeTag
    {
        $recipeTag = RecipeTag::query()
            ->whereBelongsTo($context->family)
            ->whereKey($recipeTagId)
            ->lockForUpdate()
            ->firstOrFail();
        $recipeTag->name = $name;

        try {
            $recipeTag->save();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'name' => __('A Recipe Tag with this name already exists in the Current Family.'),
            ]);
        }

        return $recipeTag;
    }
}
