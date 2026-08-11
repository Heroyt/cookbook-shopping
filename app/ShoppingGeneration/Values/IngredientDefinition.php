<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Values;

final readonly class IngredientDefinition
{
    /** @param list<AlternativeIngredientDefinition> $alternatives */
    public function __construct(
        public int $id,
        public string $name,
        public string $normalizedName,
        public IngredientPackage $package,
        public ?IngredientPlacement $placement = null,
        public array $alternatives = [],
    ) {}
}
