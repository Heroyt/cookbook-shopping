<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Values;

use LogicException;

final readonly class ShoppingListLine
{
    /**
     * @param  array<string, QuantityBreakdown>  $quantities
     * @param  list<RecipeContribution>  $contributions
     * @param  list<AlternativeIngredientDefinition>  $eligibleAlternatives
     * @param  list<AlternativeChoiceProvenance>  $alternativeChoices
     */
    public function __construct(
        public IngredientDefinition $ingredient,
        public string $purchasePackages,
        public array $quantities,
        public array $contributions,
        public array $eligibleAlternatives = [],
        public array $alternativeChoices = [],
    ) {}

    public function quantity(QuantityKind $kind): QuantityBreakdown
    {
        return $this->quantities[$kind->value]
            ?? throw new LogicException("The {$kind->value} quantity is not configured for this line.");
    }
}
