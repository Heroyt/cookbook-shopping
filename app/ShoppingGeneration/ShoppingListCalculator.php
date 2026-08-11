<?php

declare(strict_types=1);

namespace App\ShoppingGeneration;

use App\ShoppingGeneration\Values\AlternativeChoiceProvenance;
use App\ShoppingGeneration\Values\AlternativeIngredientDefinition;
use App\ShoppingGeneration\Values\CalculationProblem;
use App\ShoppingGeneration\Values\CalculationProblemReason;
use App\ShoppingGeneration\Values\ExactQuantity;
use App\ShoppingGeneration\Values\GenerationRequest;
use App\ShoppingGeneration\Values\IngredientDefinition;
use App\ShoppingGeneration\Values\IngredientPackage;
use App\ShoppingGeneration\Values\QuantityBreakdown;
use App\ShoppingGeneration\Values\QuantityKind;
use App\ShoppingGeneration\Values\RecipeContribution;
use App\ShoppingGeneration\Values\ShoppingListCalculation;
use App\ShoppingGeneration\Values\ShoppingListLine;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

final class ShoppingListCalculator
{
    public function calculate(GenerationRequest $request): ShoppingListCalculation
    {
        $requiredKinds = $this->requiredKinds($request);
        $chosenAlternatives = $this->chosenAlternatives($request, $requiredKinds);
        /** @var array<int, array{ingredient: IngredientDefinition, packageFraction: BigRational, contributions: list<RecipeContribution>, originalIngredients: array<int, IngredientDefinition>, alternativeChoices: array<int, AlternativeChoiceProvenance>}> $aggregates */
        $aggregates = [];
        $problems = [];

        foreach ($request->selections as $selection) {
            $requestedServingsProblem = $this->decimalProblemReason(
                $selection->requestedServings,
                CalculationProblemReason::NonPositiveRequestedServings,
                CalculationProblemReason::InvalidRequestedServings,
            );
            $baseServingsProblem = $this->decimalProblemReason(
                $selection->baseServings,
                CalculationProblemReason::NonPositiveBaseServings,
                CalculationProblemReason::InvalidBaseServings,
            );

            foreach ($selection->ingredients as $recipeIngredient) {
                $originalIngredient = $recipeIngredient->ingredient;
                $hasProblems = false;

                if ($requestedServingsProblem !== null) {
                    $problems[] = $this->problem(
                        $selection->recipeId,
                        $selection->recipeName,
                        $originalIngredient,
                        $selection->requestedServings,
                        'servings',
                        $requestedServingsProblem,
                    );
                    $hasProblems = true;
                }

                if ($baseServingsProblem !== null) {
                    $problems[] = $this->problem(
                        $selection->recipeId,
                        $selection->recipeName,
                        $originalIngredient,
                        $selection->baseServings,
                        'servings',
                        $baseServingsProblem,
                    );
                    $hasProblems = true;
                }

                $quantityProblem = $this->decimalProblemReason(
                    $recipeIngredient->quantity,
                    CalculationProblemReason::NonPositiveRecipeQuantity,
                    CalculationProblemReason::InvalidRecipeQuantity,
                );
                if ($quantityProblem !== null) {
                    $problems[] = $this->problem(
                        $selection->recipeId,
                        $selection->recipeName,
                        $originalIngredient,
                        $recipeIngredient->quantity,
                        $recipeIngredient->quantityKind->value,
                        $quantityProblem,
                    );
                    $hasProblems = true;
                }

                $packageIsValid = $this->packageIsValid($originalIngredient->package);
                if ( ! $packageIsValid) {
                    $problems[] = $this->problem(
                        $selection->recipeId,
                        $selection->recipeName,
                        $originalIngredient,
                        $recipeIngredient->quantity,
                        $recipeIngredient->quantityKind->value,
                        CalculationProblemReason::InvalidPackageDefinition,
                    );
                    $hasProblems = true;
                }

                if ($packageIsValid && $originalIngredient->package->quantityFor($recipeIngredient->quantityKind) === null) {
                    $problems[] = $this->problem(
                        $selection->recipeId,
                        $selection->recipeName,
                        $originalIngredient,
                        $recipeIngredient->quantity,
                        $recipeIngredient->quantityKind->value,
                        CalculationProblemReason::MissingPackageQuantity,
                    );
                    $hasProblems = true;
                }

                if ($hasProblems) {
                    continue;
                }

                $requestedServings = BigRational::of($selection->requestedServings);
                $baseServings = BigRational::of($selection->baseServings);
                $quantity = BigRational::of($recipeIngredient->quantity);

                $chosenAlternative = $chosenAlternatives[$originalIngredient->id] ?? null;
                $finalIngredient = $chosenAlternative?->asIngredient() ?? $originalIngredient;
                $finalPackageQuantity = $finalIngredient->package->quantityFor($recipeIngredient->quantityKind);
                if ($finalPackageQuantity === null) {
                    throw new InvalidArgumentException('A chosen Alternative must define every required canonical quantity kind.');
                }

                $servingFactor = $requestedServings->dividedBy($baseServings);
                $required = $quantity->multipliedBy($servingFactor);
                $packageFraction = $required->dividedBy($finalPackageQuantity);
                $finalIngredientId = $finalIngredient->id;
                $aggregate = $aggregates[$finalIngredientId] ?? [
                    'ingredient' => $finalIngredient,
                    'packageFraction' => BigRational::zero(),
                    'contributions' => [],
                    'originalIngredients' => [],
                    'alternativeChoices' => [],
                ];
                $aggregate['packageFraction'] = $aggregate['packageFraction']->plus($packageFraction);
                $aggregate['originalIngredients'][$originalIngredient->id] = $originalIngredient;

                if ($chosenAlternative !== null) {
                    $aggregate['alternativeChoices'][$originalIngredient->id] = new AlternativeChoiceProvenance(
                        originalIngredientId: $originalIngredient->id,
                        originalIngredientName: $originalIngredient->name,
                        alternativeIngredientId: $chosenAlternative->id,
                        alternativeIngredientName: $chosenAlternative->name,
                    );
                }

                $aggregate['contributions'][] = new RecipeContribution(
                    recipeId: $selection->recipeId,
                    recipeName: $selection->recipeName,
                    originalIngredientId: $originalIngredient->id,
                    originalIngredientName: $originalIngredient->name,
                    quantityKind: $recipeIngredient->quantityKind,
                    required: ExactQuantity::from($required),
                    packageFraction: ExactQuantity::from($packageFraction),
                );
                $aggregates[$finalIngredientId] = $aggregate;
            }
        }

        if ($problems !== []) {
            return ShoppingListCalculation::failed($problems);
        }

        $lines = [];
        foreach ($aggregates as $aggregate) {
            $packageFraction = $aggregate['packageFraction'];
            $purchasePackages = $packageFraction->toScale(0, RoundingMode::Ceiling)->toBigInteger();
            $quantities = [];

            foreach ($aggregate['ingredient']->package->kinds() as $kind) {
                $packageQuantity = BigRational::of($aggregate['ingredient']->package->quantityFor($kind) ?? 0);
                $required = $packageFraction->multipliedBy($packageQuantity);
                $purchased = BigRational::of($purchasePackages)->multipliedBy($packageQuantity);
                $quantities[$kind->value] = new QuantityBreakdown(
                    required: ExactQuantity::from($required),
                    purchased: ExactQuantity::from($purchased),
                    surplus: ExactQuantity::from($purchased->minus($required)),
                );
            }

            usort(
                $aggregate['contributions'],
                static fn (RecipeContribution $left, RecipeContribution $right): int => [
                    $left->recipeId,
                    $left->originalIngredientId,
                    $left->quantityKind->value,
                ] <=> [
                    $right->recipeId,
                    $right->originalIngredientId,
                    $right->quantityKind->value,
                ],
            );
            $originalIngredients = array_values($aggregate['originalIngredients']);
            $alternativeChoices = array_values($aggregate['alternativeChoices']);
            usort(
                $alternativeChoices,
                static fn (AlternativeChoiceProvenance $left, AlternativeChoiceProvenance $right): int => $left->originalIngredientId <=> $right->originalIngredientId,
            );
            $eligibleAlternatives = [];

            if (count($originalIngredients) === 1 && $alternativeChoices === []) {
                $originalIngredient = $originalIngredients[0];
                $eligibleAlternatives = $this->eligibleAlternatives(
                    $originalIngredient,
                    $requiredKinds[$originalIngredient->id] ?? [],
                );
            }

            $lines[] = new ShoppingListLine(
                ingredient: $aggregate['ingredient'],
                purchasePackages: (string) $purchasePackages,
                quantities: $quantities,
                contributions: $aggregate['contributions'],
                eligibleAlternatives: $eligibleAlternatives,
                alternativeChoices: $alternativeChoices,
            );
        }

        return ShoppingListCalculation::successful($lines);
    }

    private function positiveDecimal(string $value): ?BigRational
    {
        if (preg_match('/^-?\d{1,14}(?:\.\d{1,6})?$/', $value) !== 1) {
            return null;
        }

        $quantity = BigRational::of($value);

        return $quantity->isPositive() ? $quantity : null;
    }

    private function decimalProblemReason(
        string $value,
        CalculationProblemReason $nonPositiveReason,
        CalculationProblemReason $invalidReason,
    ): ?CalculationProblemReason {
        if (preg_match('/^-?\d{1,14}(?:\.\d{1,6})?$/', $value) !== 1) {
            return $invalidReason;
        }

        return BigRational::of($value)->isPositive() ? null : $nonPositiveReason;
    }

    private function packageIsValid(IngredientPackage $package): bool
    {
        if ($package->weightGrams !== null && $package->volumeMillilitres !== null) {
            return false;
        }

        if ($package->kinds() === []) {
            return false;
        }

        foreach ($package->kinds() as $kind) {
            $quantity = $package->quantityFor($kind);
            if ($quantity === null || $this->positiveDecimal($quantity) === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, list<QuantityKind>>  $requiredKinds
     * @return array<int, AlternativeIngredientDefinition>
     */
    private function chosenAlternatives(GenerationRequest $request, array $requiredKinds): array
    {
        $ingredients = [];
        foreach ($request->selections as $selection) {
            foreach ($selection->ingredients as $recipeIngredient) {
                $ingredients[$recipeIngredient->ingredient->id] = $recipeIngredient->ingredient;
            }
        }

        $chosen = [];
        foreach ($request->alternativeChoices as $choice) {
            if (isset($chosen[$choice->originalIngredientId])) {
                throw new InvalidArgumentException('An original Ingredient may have only one Alternative choice.');
            }

            $original = $ingredients[$choice->originalIngredientId] ?? null;
            if ( ! $original instanceof IngredientDefinition) {
                throw new InvalidArgumentException('An Alternative choice must target an originally generated Ingredient.');
            }

            $alternative = null;
            foreach ($original->alternatives as $candidate) {
                if ($candidate->id === $choice->alternativeIngredientId) {
                    $alternative = $candidate;

                    break;
                }
            }

            if ( ! $alternative instanceof AlternativeIngredientDefinition
                || ! $alternative->active
                || ! $this->packageIsValid($alternative->package)
                || ! $this->packageHasKinds($alternative->package, $requiredKinds[$original->id] ?? [])
            ) {
                throw new InvalidArgumentException('An Alternative choice must be direct, active, and canonical-kind compatible.');
            }

            $chosen[$original->id] = $alternative;
        }

        return $chosen;
    }

    /** @return array<int, list<QuantityKind>> */
    private function requiredKinds(GenerationRequest $request): array
    {
        $required = [];

        foreach ($request->selections as $selection) {
            foreach ($selection->ingredients as $recipeIngredient) {
                $required[$recipeIngredient->ingredient->id][$recipeIngredient->quantityKind->value]
                    = $recipeIngredient->quantityKind;
            }
        }

        return array_map(static fn (array $kinds): array => array_values($kinds), $required);
    }

    /**
     * @param  list<QuantityKind>  $requiredKinds
     * @return list<AlternativeIngredientDefinition>
     */
    private function eligibleAlternatives(IngredientDefinition $ingredient, array $requiredKinds): array
    {
        $eligible = array_values(array_filter(
            $ingredient->alternatives,
            fn (AlternativeIngredientDefinition $alternative): bool => $alternative->active
                && $this->packageIsValid($alternative->package)
                && $this->packageHasKinds($alternative->package, $requiredKinds),
        ));
        usort(
            $eligible,
            static fn (AlternativeIngredientDefinition $left, AlternativeIngredientDefinition $right): int => NormalizedNameComparator::compare(
                $left->normalizedName,
                $left->id,
                $right->normalizedName,
                $right->id,
            ),
        );

        return $eligible;
    }

    /** @param  list<QuantityKind>  $requiredKinds */
    private function packageHasKinds(IngredientPackage $package, array $requiredKinds): bool
    {
        foreach ($requiredKinds as $kind) {
            if ($package->quantityFor($kind) === null) {
                return false;
            }
        }

        return true;
    }

    private function problem(
        int $recipeId,
        string $recipeName,
        IngredientDefinition $ingredient,
        string $quantity,
        string $unit,
        CalculationProblemReason $reason,
    ): CalculationProblem {
        return new CalculationProblem(
            recipeId: $recipeId,
            recipeName: $recipeName,
            ingredientId: $ingredient->id,
            ingredientName: $ingredient->name,
            quantity: $quantity,
            unit: $unit,
            reason: $reason,
        );
    }
}
