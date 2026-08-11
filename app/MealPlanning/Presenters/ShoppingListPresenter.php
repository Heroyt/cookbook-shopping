<?php

declare(strict_types=1);

namespace App\MealPlanning\Presenters;

use App\ShoppingGeneration\Values\CalculationProblem;
use App\ShoppingGeneration\Values\CalculationProblemReason;
use App\ShoppingGeneration\Values\ExactQuantity;
use App\ShoppingGeneration\Values\GenerationResult;
use App\ShoppingGeneration\Values\QuantityBreakdown;
use App\ShoppingGeneration\Values\QuantityKind;
use App\ShoppingGeneration\Values\RecipeContribution;
use App\ShoppingGeneration\Values\ShoppingListLine;
use App\ShoppingGeneration\Values\StoreGroup;
use App\ShoppingGeneration\Values\StoreSectionGroup;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;

final class ShoppingListPresenter
{
    /** @return array{shoppingList: array<string, mixed>|null, problems: list<array<string, mixed>>} */
    public function present(GenerationResult $result): array
    {
        if ($result->shoppingList === null) {
            return [
                'shoppingList' => null,
                'problems' => array_map(fn (CalculationProblem $problem): array => $this->problem($problem), $result->problems),
            ];
        }

        return [
            'shoppingList' => [
                'storeGroups' => array_map(fn (StoreGroup $group): array => [
                    'storeId' => $group->store->id,
                    'storeName' => $group->store->name,
                    'sections' => array_map(fn (StoreSectionGroup $section): array => [
                        'sectionId' => $section->section->id,
                        'sectionName' => $section->section->name,
                        'lines' => array_map(fn (ShoppingListLine $line): array => $this->line($line), $section->lines),
                    ], $group->sections),
                    'unsectionedLines' => array_map(fn (ShoppingListLine $line): array => $this->line($line), $group->unsectionedLines),
                ], $result->shoppingList->storeGroups),
                'unplacedLines' => array_map(fn (ShoppingListLine $line): array => $this->line($line), $result->shoppingList->unplacedLines),
            ],
            'problems' => [],
        ];
    }

    /** @return array<string, mixed> */
    private function line(ShoppingListLine $line): array
    {
        $quantities = [];
        foreach (QuantityKind::cases() as $kind) {
            $breakdown = $line->quantities[$kind->value] ?? null;
            if ( ! $breakdown instanceof QuantityBreakdown) {
                continue;
            }
            $quantities[] = [
                'kind' => $kind->value,
                'required' => $this->quantity($breakdown->required, $kind),
                'purchased' => $this->quantity($breakdown->purchased, $kind),
                'surplus' => $this->quantity($breakdown->surplus, $kind),
            ];
        }

        return [
            'ingredientId' => $line->ingredient->id,
            'ingredientName' => $line->ingredient->name,
            'purchasePackages' => $line->purchasePackages,
            'quantities' => $quantities,
            'contributions' => array_map(fn (RecipeContribution $contribution): array => [
                'recipeId' => $contribution->recipeId,
                'recipeName' => $contribution->recipeName,
                'originalIngredientName' => $contribution->originalIngredientName,
                'required' => $this->quantity($contribution->required, $contribution->quantityKind),
            ], $line->contributions),
            'eligibleAlternatives' => array_map(static fn ($alternative): array => [
                'ingredientId' => $alternative->id,
                'ingredientName' => $alternative->name,
            ], $line->eligibleAlternatives),
            'alternativeChoices' => array_map(static fn ($choice): array => [
                'originalIngredientName' => $choice->originalIngredientName,
                'alternativeIngredientName' => $choice->alternativeIngredientName,
            ], $line->alternativeChoices),
        ];
    }

    /** @return array{label: string, value: string, unit: string, approximate: bool} */
    private function quantity(ExactQuantity $quantity, QuantityKind $kind): array
    {
        $value = BigRational::of($quantity->fraction());
        $unit = match ($kind) {
            QuantityKind::Grams => 'g',
            QuantityKind::Millilitres => 'ml',
            QuantityKind::Piece => 'ks',
        };
        if ($kind === QuantityKind::Grams && $value->isGreaterThanOrEqualTo(1000)) {
            $value = $value->dividedBy(1000);
            $unit = 'kg';
        }
        if ($kind === QuantityKind::Millilitres && $value->isGreaterThanOrEqualTo(1000)) {
            $value = $value->dividedBy(1000);
            $unit = 'l';
        }

        $rounded = $value->toScale(2, RoundingMode::HalfUp);
        $normalized = $this->normalizeDecimal((string) $rounded);

        return [
            'label' => str_replace('.', ',', $normalized) . ' ' . $unit,
            'value' => $normalized,
            'unit' => $unit,
            'approximate' => BigRational::of($normalized)->compareTo($value) !== 0,
        ];
    }

    /** @return array<string, mixed> */
    private function problem(CalculationProblem $problem): array
    {
        return [
            'recipeId' => $problem->recipeId,
            'recipeName' => $problem->recipeName,
            'ingredientId' => $problem->ingredientId,
            'ingredientName' => $problem->ingredientName,
            'quantity' => $problem->quantity,
            'unit' => $problem->unit,
            'message' => match ($problem->reason) {
                CalculationProblemReason::NonPositiveRequestedServings,
                CalculationProblemReason::InvalidRequestedServings => __('The requested Serving Count is invalid.'),
                CalculationProblemReason::NonPositiveBaseServings,
                CalculationProblemReason::InvalidBaseServings => __('The Recipe base Serving Count is invalid.'),
                CalculationProblemReason::NonPositiveRecipeQuantity,
                CalculationProblemReason::InvalidRecipeQuantity => __('A Recipe Ingredient quantity is invalid.'),
                CalculationProblemReason::MissingPackageQuantity => __('The Ingredient package is missing the required quantity kind.'),
                CalculationProblemReason::InvalidPackageDefinition => __('The Ingredient package definition is invalid.'),
            },
        ];
    }

    private function normalizeDecimal(string $value): string
    {
        return str_contains($value, '.') ? rtrim(rtrim($value, '0'), '.') : $value;
    }
}
