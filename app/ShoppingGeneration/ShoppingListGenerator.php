<?php

declare(strict_types=1);

namespace App\ShoppingGeneration;

use App\ShoppingGeneration\Values\GenerationRequest;
use App\ShoppingGeneration\Values\GenerationResult;

final readonly class ShoppingListGenerator
{
    public function __construct(
        private ShoppingListCalculator $calculator,
        private ShoppingListGrouper $grouper,
    ) {}

    public function generate(GenerationRequest $request): GenerationResult
    {
        $calculation = $this->calculator->calculate($request);

        if ($calculation->problems !== []) {
            return GenerationResult::failed($calculation->problems);
        }

        return GenerationResult::successful($this->grouper->group($calculation->lines));
    }
}
