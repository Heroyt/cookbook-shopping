<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Values;

final readonly class StoreGroup
{
    /**
     * @param  list<StoreSectionGroup>  $sections
     * @param  list<ShoppingListLine>  $unsectionedLines
     */
    public function __construct(
        public StoreReference $store,
        public array $sections,
        public array $unsectionedLines,
    ) {}
}
