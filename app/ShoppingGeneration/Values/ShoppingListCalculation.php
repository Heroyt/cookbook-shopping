<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Values;

final readonly class ShoppingListCalculation
{
    /**
     * @param  list<ShoppingListLine>  $lines
     * @param  list<CalculationProblem>  $problems
     */
    private function __construct(
        public array $lines,
        public array $problems,
    ) {}

    /** @param list<ShoppingListLine> $lines */
    public static function successful(array $lines): self
    {
        return new self($lines, []);
    }

    /** @param list<CalculationProblem> $problems */
    public static function failed(array $problems): self
    {
        return new self([], $problems);
    }
}
