<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Values;

final readonly class IngredientPlacement
{
    public function __construct(
        public StoreReference $store,
        public ?StoreSectionReference $section = null,
    ) {}
}
