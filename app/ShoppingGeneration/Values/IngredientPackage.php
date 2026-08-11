<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Values;

final readonly class IngredientPackage
{
    public function __construct(
        public ?string $weightGrams = null,
        public ?string $volumeMillilitres = null,
        public ?string $pieceCount = null,
    ) {}

    public function quantityFor(QuantityKind $kind): ?string
    {
        return match ($kind) {
            QuantityKind::Grams => $this->weightGrams,
            QuantityKind::Millilitres => $this->volumeMillilitres,
            QuantityKind::Piece => $this->pieceCount,
        };
    }

    /** @return list<QuantityKind> */
    public function kinds(): array
    {
        return array_values(array_filter(
            QuantityKind::cases(),
            fn (QuantityKind $kind): bool => $this->quantityFor($kind) !== null,
        ));
    }
}
