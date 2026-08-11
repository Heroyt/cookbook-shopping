<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Values;

final readonly class AlternativeChoiceProvenance
{
    public function __construct(
        public int $originalIngredientId,
        public string $originalIngredientName,
        public int $alternativeIngredientId,
        public string $alternativeIngredientName,
    ) {}
}
