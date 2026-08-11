<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Values;

final readonly class RecipeContribution
{
    public function __construct(
        public int $recipeId,
        public string $recipeName,
        public int $originalIngredientId,
        public string $originalIngredientName,
        public QuantityKind $quantityKind,
        public ExactQuantity $required,
        public ExactQuantity $packageFraction,
    ) {}
}
