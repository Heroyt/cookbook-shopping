<?php

declare(strict_types=1);

namespace App\Cookbook\Values;

final readonly class IngredientStorePlacement
{
    public function __construct(
        public ?int $storeId,
        public ?int $storeSectionId,
    ) {}
}
