<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Values\IngredientNutritionInput;
use App\Cookbook\Values\IngredientPackageQuantities;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Models\User;
use Illuminate\Database\QueryException;
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
        ?IngredientNutritionInput $nutrition,
    ): Ingredient {
        return $this->currentFamilyScope->within(
            $user,
            fn (Family $family): Ingredient => DB::transaction(function () use ($family, $ingredientId, $name, $description, $quantities, $storeId, $storeSectionId, $nutrition): Ingredient {
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

                if ($nutrition !== null && ! $nutrition->supports($quantities)) {
                    $savedBasisKind = $ingredient->nutritionProfile()->value('basis_kind');
                    $removesSavedBasis = is_string($savedBasisKind) && $savedBasisKind === $nutrition->basisKind;
                    $field = $removesSavedBasis
                        ? ($nutrition->basisKind === 'piece' ? 'piece_count' : 'metric_quantity')
                        : 'nutrition_basis_kind';
                    $message = $removesSavedBasis
                        ? ($nutrition->basisKind === 'piece'
                            ? __('The piece count cannot be removed because the Nutrition Profile uses it.')
                            : __('The metric quantity cannot be removed because the Nutrition Profile uses it.'))
                        : __('The Nutrition Profile basis is unavailable in this package.');
                    throw ValidationException::withMessages([$field => $message]);
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
                } catch (QueryException $exception) {
                    $this->resolveIngredientStorePlacement->rethrowAsValidationExceptionIfUnavailable(
                        $family,
                        $placement,
                        $exception,
                    );
                }

                if ($nutrition === null) {
                    $ingredient->nutritionProfile()->delete();
                } else {
                    $ingredient->nutritionProfile()->updateOrCreate([], $nutrition->persistence());
                }

                return $ingredient;
            }),
        );
    }
}
