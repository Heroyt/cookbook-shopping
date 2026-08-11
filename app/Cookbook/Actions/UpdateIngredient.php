<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\RecipeIngredient;
use App\Cookbook\Values\IngredientNutritionInput;
use App\Cookbook\Values\IngredientPackageQuantities;
use App\FamilyAccess\AuthorizedFamilyContext;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateIngredient
{
    public function __construct(
        private ResolveIngredientStorePlacement $resolveIngredientStorePlacement,
    ) {}

    public function handle(
        AuthorizedFamilyContext $context,
        int $ingredientId,
        string $name,
        ?string $description,
        IngredientPackageQuantities $quantities,
        ?int $storeId,
        ?int $storeSectionId,
        ?IngredientNutritionInput $nutrition,
    ): Ingredient {
        return DB::transaction(function () use ($context, $ingredientId, $name, $description, $quantities, $storeId, $storeSectionId, $nutrition): Ingredient {
            $ingredient = Ingredient::query()
                ->whereBelongsTo($context->family)
                ->whereKey($ingredientId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($ingredient->archived_at !== null) {
                throw ValidationException::withMessages([
                    'ingredient' => __('Restore the Ingredient before editing it.'),
                ]);
            }

            $removedKinds = array_values(array_filter([
                $ingredient->weight_grams !== null && $quantities->weightGrams === null ? 'grams' : null,
                $ingredient->volume_millilitres !== null && $quantities->volumeMillilitres === null ? 'millilitres' : null,
                $ingredient->piece_count !== null && $quantities->pieceCount === null ? 'piece' : null,
            ]));
            if ($removedKinds !== []) {
                $usedKind = RecipeIngredient::query()
                    ->where('family_id', $context->family->id)
                    ->where('ingredient_id', $ingredient->id)
                    ->whereIn('quantity_kind', $removedKinds)
                    ->lockForUpdate()
                    ->value('quantity_kind');
                if (is_string($usedKind)) {
                    $field = $usedKind === 'piece' ? 'piece_count' : 'metric_quantity';
                    $message = $usedKind === 'piece'
                        ? __('The piece count cannot be removed because a Recipe Ingredient uses it.')
                        : __('The metric quantity cannot be removed because a Recipe Ingredient uses it.');
                    throw ValidationException::withMessages([$field => $message]);
                }
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
            $placement = $this->resolveIngredientStorePlacement->handle($context->family, $storeId, $storeSectionId);

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
                    $context->family,
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
        });
    }
}
