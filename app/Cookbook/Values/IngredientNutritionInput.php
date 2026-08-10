<?php

declare(strict_types=1);

namespace App\Cookbook\Values;

final readonly class IngredientNutritionInput
{
    public function __construct(
        public string $basisKind,
        public string $basisQuantity,
        public string $energyKcal,
        public string $fatGrams,
        public string $proteinGrams,
        public string $carbohydrateGrams,
    ) {}

    /** @return array<string, string> */
    public function persistence(): array
    {
        return [
            'basis_kind' => $this->basisKind, 'basis_quantity' => $this->basisQuantity,
            'energy_kcal' => $this->energyKcal, 'fat_grams' => $this->fatGrams,
            'protein_grams' => $this->proteinGrams, 'carbohydrate_grams' => $this->carbohydrateGrams,
        ];
    }

    public function supports(IngredientPackageQuantities $quantities): bool
    {
        return match ($this->basisKind) {
            'grams' => $quantities->weightGrams !== null,
            'millilitres' => $quantities->volumeMillilitres !== null,
            'piece' => $quantities->pieceCount !== null,
            default => true,
        };
    }
}
