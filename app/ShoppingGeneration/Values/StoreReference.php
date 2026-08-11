<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Values;

final readonly class StoreReference
{
    public function __construct(
        public int $id,
        public string $name,
        public string $normalizedName,
    ) {}
}
