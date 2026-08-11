<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Values;

use InvalidArgumentException;

final readonly class RecipeSelection
{
    /** @param list<RecipeIngredientInput> $ingredients */
    public function __construct(
        public int $recipeId,
        public string $recipeName,
        public string $baseServings,
        public string $requestedServings,
        public array $ingredients,
    ) {
        if ($this->ingredients === []) {
            throw new InvalidArgumentException('A Recipe Selection requires at least one Recipe Ingredient.');
        }
    }
}
