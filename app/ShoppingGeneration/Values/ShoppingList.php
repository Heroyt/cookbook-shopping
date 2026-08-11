<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Values;

final readonly class ShoppingList
{
    /**
     * @param  list<StoreGroup>  $storeGroups
     * @param  list<ShoppingListLine>  $unplacedLines
     */
    public function __construct(
        public array $storeGroups,
        public array $unplacedLines,
    ) {}
}
