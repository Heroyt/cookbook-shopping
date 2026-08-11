<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Values;

final readonly class QuantityBreakdown
{
    public function __construct(
        public ExactQuantity $required,
        public ExactQuantity $purchased,
        public ExactQuantity $surplus,
    ) {}
}
