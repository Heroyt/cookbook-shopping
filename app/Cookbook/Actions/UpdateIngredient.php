<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Values\IngredientPackageQuantities;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateIngredient
{
    public function __construct(
        private CurrentFamilyScope $currentFamilyScope,
        private ResolveIngredientStorePlacement $resolveIngredientStorePlacement,
    ) {}

    public function handle(
        User $user,
        int $ingredientId,
        string $name,
        ?string $description,
        IngredientPackageQuantities $quantities,
        ?int $storeId,
        ?int $storeSectionId,
    ): Ingredient {
        return $this->currentFamilyScope->within(
            $user,
            fn (Family $family): Ingredient => DB::transaction(function () use ($family, $ingredientId, $name, $description, $quantities, $storeId, $storeSectionId): Ingredient {
                $ingredient = Ingredient::query()
                    ->whereBelongsTo($family)
                    ->whereKey($ingredientId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($ingredient->archived_at !== null) {
                    throw ValidationException::withMessages([
                        'ingredient' => __('Restore the Ingredient before editing it.'),
                    ]);
                }
                $placement = $this->resolveIngredientStorePlacement->handle($family, $storeId, $storeSectionId);

                $ingredient->fill([
                    'name' => $name,
                    'description' => $description,
                    'weight_grams' => $quantities->weightGrams,
                    'volume_millilitres' => $quantities->volumeMillilitres,
                    'piece_count' => $quantities->pieceCount,
                    'store_id' => $placement->storeId,
                    'store_section_id' => $placement->storeSectionId,
                ]);

                try {
                    $ingredient->save();
                } catch (UniqueConstraintViolationException) {
                    throw ValidationException::withMessages([
                        'name' => __('An Ingredient with this name already exists in the Current Family.'),
                    ]);
                }

                return $ingredient;
            }),
        );
    }
}
