<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Values;

final readonly class RecipeIngredientInput
{
    public function __construct(
        public IngredientDefinition $ingredient,
        public string $quantity,
        public QuantityKind $quantityKind,
    ) {}
}
