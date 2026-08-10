<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Values\IngredientPackageQuantities;
use App\Cookbook\Values\NormalizedName;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final readonly class CreateIngredient
{
    public function __construct(private CurrentFamilyScope $currentFamilyScope) {}

    public function handle(User $user, string $name, IngredientPackageQuantities $quantities): Ingredient
    {
        return $this->currentFamilyScope->within($user, function (Family $family) use ($name, $quantities): Ingredient {
            $normalizedName = NormalizedName::from($name);
            $ingredient = Ingredient::query()->createOrFirst(
                [
                    'family_id' => $family->id,
                    'normalized_name' => $normalizedName->key,
                ],
                [
                    'name' => $normalizedName->display,
                    'weight_grams' => $quantities->weightGrams,
                    'volume_millilitres' => $quantities->volumeMillilitres,
                    'piece_count' => $quantities->pieceCount,
                ],
            );

            if ( ! $ingredient->wasRecentlyCreated) {
                throw ValidationException::withMessages([
                    'name' => __('An Ingredient with this name already exists in the Current Family.'),
                ]);
            }

            return $ingredient;
        });
    }
}
