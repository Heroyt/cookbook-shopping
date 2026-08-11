<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Values;

final readonly class GenerationRequest
{
    /**
     * @param  list<RecipeSelection>  $selections
     * @param  list<AlternativeChoice>  $alternativeChoices
     */
    public function __construct(
        public array $selections,
        public array $alternativeChoices = [],
    ) {}
}
