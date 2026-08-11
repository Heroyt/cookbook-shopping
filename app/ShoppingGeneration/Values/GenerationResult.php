<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Values;

final readonly class GenerationResult
{
    /** @param list<CalculationProblem> $problems */
    private function __construct(
        public ?ShoppingList $shoppingList,
        public array $problems,
    ) {}

    public static function successful(ShoppingList $shoppingList): self
    {
        return new self($shoppingList, []);
    }

    /** @param list<CalculationProblem> $problems */
    public static function failed(array $problems): self
    {
        return new self(null, $problems);
    }

    public function isSuccessful(): bool
    {
        return $this->shoppingList !== null;
    }
}
