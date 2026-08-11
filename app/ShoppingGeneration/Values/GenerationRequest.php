<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Values;

use InvalidArgumentException;

final readonly class GenerationRequest
{
    /**
     * @param  list<RecipeSelection>  $selections
     * @param  list<AlternativeChoice>  $alternativeChoices
     */
    public function __construct(
        public array $selections,
        public array $alternativeChoices = [],
    ) {
        if ($this->selections === []) {
            throw new InvalidArgumentException('Shopping Generation requires at least one Recipe Selection.');
        }
    }
}
