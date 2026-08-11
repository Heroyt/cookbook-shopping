<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Values;

use Brick\Math\BigRational;

final readonly class ExactQuantity
{
    private function __construct(private BigRational $value) {}

    public static function from(BigRational $value): self
    {
        return new self($value);
    }

    public function fraction(): string
    {
        return (string) $this->value;
    }
}
