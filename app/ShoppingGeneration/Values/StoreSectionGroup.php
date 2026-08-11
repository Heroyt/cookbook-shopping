<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Values;

final readonly class StoreSectionGroup
{
    /** @param list<ShoppingListLine> $lines */
    public function __construct(
        public StoreSectionReference $section,
        public array $lines,
    ) {}
}
