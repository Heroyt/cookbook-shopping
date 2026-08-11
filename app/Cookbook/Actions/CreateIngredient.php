<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Values\IngredientNutritionInput;
use App\Cookbook\Values\IngredientPackageQuantities;
use App\Cookbook\Values\NormalizedName;
use App\FamilyAccess\AuthorizedFamilyContext;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

final readonly class CreateIngredient
{
    public function __construct(
        private ResolveIngredientStorePlacement $resolveIngredientStorePlacement,
    ) {}

    public function handle(
        AuthorizedFamilyContext $context,
        string $name,
        ?string $description,
        IngredientPackageQuantities $quantities,
        ?int $storeId,
        ?int $storeSectionId,
        ?IngredientNutritionInput $nutrition,
    ): Ingredient {
        $this->validateNutritionCompatibility($nutrition, $quantities);
        $placement = $this->resolveIngredientStorePlacement->handle($context->family, $storeId, $storeSectionId);
        $normalizedName = NormalizedName::from($name);
        try {
            $ingredient = Ingredient::query()->createOrFirst(
                [
                    'family_id' => $context->family->id,
                    'normalized_name' => $normalizedName->key,
                ],
                [
                    'name' => $normalizedName->display,
                    'description' => $description,
                    'weight_grams' => $quantities->weightGrams,
                    'volume_millilitres' => $quantities->volumeMillilitres,
                    'piece_count' => $quantities->pieceCount,
                    'store_id' => $placement->storeId,
                    'store_section_id' => $placement->storeSectionId,
                ],
            );
        } catch (QueryException $exception) {
            $this->resolveIngredientStorePlacement->rethrowAsValidationExceptionIfUnavailable(
                $context->family,
                $placement,
                $exception,
            );
        }

        if ( ! $ingredient->wasRecentlyCreated) {
            throw ValidationException::withMessages([
                'name' => __('An Ingredient with this name already exists in the Current Family.'),
            ]);
        }

        if ($nutrition !== null) {
            $ingredient->nutritionProfile()->create($nutrition->persistence());
        }

        return $ingredient;
    }

    private function validateNutritionCompatibility(?IngredientNutritionInput $nutrition, IngredientPackageQuantities $quantities): void
    {
        if ($nutrition !== null && ! $nutrition->supports($quantities)) {
            throw ValidationException::withMessages([
                'nutrition_basis_kind' => __('The Nutrition Profile basis is unavailable in this package.'),
            ]);
        }
    }
}
