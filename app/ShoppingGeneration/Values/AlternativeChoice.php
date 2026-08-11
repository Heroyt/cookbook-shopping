<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Values;

final readonly class AlternativeChoice
{
    public function __construct(
        public int $originalIngredientId,
        public int $alternativeIngredientId,
    ) {}
}
