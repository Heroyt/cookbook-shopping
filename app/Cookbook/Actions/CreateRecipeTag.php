<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\RecipeTag;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

final readonly class CreateRecipeTag
{
    public function __construct(private CurrentFamilyScope $scope) {}

    public function handle(User $user, string $name): RecipeTag
    {
        return $this->scope->within($user, function (Family $family) use ($name): RecipeTag {
            try {
                return RecipeTag::query()->create(['family_id' => $family->id, 'name' => $name]);
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['name' => __('A Recipe Tag with this name already exists in the Current Family.')]);
            }
        });
    }
}
