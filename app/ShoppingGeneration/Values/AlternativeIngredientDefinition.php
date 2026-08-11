<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Values;

final readonly class AlternativeIngredientDefinition
{
    public function __construct(
        public int $id,
        public string $name,
        public string $normalizedName,
        public IngredientPackage $package,
        public bool $active,
        public ?IngredientPlacement $placement = null,
    ) {}

    public function asIngredient(): IngredientDefinition
    {
        return new IngredientDefinition(
            id: $this->id,
            name: $this->name,
            normalizedName: $this->normalizedName,
            package: $this->package,
            placement: $this->placement,
        );
    }
}
