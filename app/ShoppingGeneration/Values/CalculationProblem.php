<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Values;

final readonly class CalculationProblem
{
    public function __construct(
        public int $recipeId,
        public string $recipeName,
        public int $ingredientId,
        public string $ingredientName,
        public string $quantity,
        public string $unit,
        public CalculationProblemReason $reason,
    ) {}
}
