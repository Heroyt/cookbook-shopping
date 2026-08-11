<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\RecipeTag;
use App\FamilyAccess\AuthorizedFamilyContext;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

final readonly class CreateRecipeTag
{
    public function handle(AuthorizedFamilyContext $context, string $name): RecipeTag
    {
        try {
            return RecipeTag::query()->create(['family_id' => $context->family->id, 'name' => $name]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => __('A Recipe Tag with this name already exists in the Current Family.')]);
        }
    }
}
