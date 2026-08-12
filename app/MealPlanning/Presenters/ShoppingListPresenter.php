<?php

declare(strict_types=1);

namespace App\MealPlanning\Presenters;

use App\Cookbook\Models\Store;
use App\Cookbook\Models\StoreSection;
use App\Cookbook\Services\EntityMediaStorage;
use App\Cookbook\Values\EntityMediaType;
use App\FamilyAccess\Models\Family;
use App\ShoppingGeneration\Values\AlternativeChoiceProvenance;
use App\ShoppingGeneration\Values\AlternativeIngredientDefinition;
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
use Illuminate\Support\Collection;

final readonly class ShoppingListPresenter
{
    public function __construct(private EntityMediaStorage $entityMediaStorage) {}

    /** @return array{shoppingList: array<string, mixed>|null, problems: list<array<string, mixed>>} */
    public function present(GenerationResult $result, Family $family): array
    {
        if ($result->shoppingList === null) {
            return [
                'shoppingList' => null,
                'problems' => array_map(
                    fn (CalculationProblem $problem, int $index): array => $this->problem($problem, $index),
                    $result->problems,
                    array_keys($result->problems),
                ),
            ];
        }

        $storeIds = array_map(static fn (StoreGroup $group): int => $group->store->id, $result->shoppingList->storeGroups);
        $sectionIds = [];
        foreach ($result->shoppingList->storeGroups as $group) {
            foreach ($group->sections as $section) {
                $sectionIds[] = $section->section->id;
            }
        }
        $stores = Store::query()->whereBelongsTo($family)->whereIn('id', $storeIds)->get()->keyBy('id');
        $sections = StoreSection::query()->whereBelongsTo($family)->whereIn('id', $sectionIds)->get()->keyBy('id');

        return [
            'shoppingList' => [
                'storeGroups' => array_map(fn (StoreGroup $group): array => [
                    'storeId' => $group->store->id,
                    'storeName' => $group->store->name,
                    'storeLogoUrl' => $this->storeLogoUrl($family, $stores, $group->store->id),
                    'sections' => array_map(fn (StoreSectionGroup $section): array => [
                        'sectionId' => $section->section->id,
                        'sectionName' => $section->section->name,
                        ...$this->sectionVisuals($family, $sections, $section->section->id),
                        'lines' => array_map(fn (ShoppingListLine $line): array => $this->line($line), $section->lines),
                    ], $group->sections),
                    'unsectionedLines' => array_map(fn (ShoppingListLine $line): array => $this->line($line), $group->unsectionedLines),
                ], $result->shoppingList->storeGroups),
                'unplacedLines' => array_map(fn (ShoppingListLine $line): array => $this->line($line), $result->shoppingList->unplacedLines),
            ],
            'problems' => [],
        ];
    }

    /** @param Collection<int, Store> $stores */
    private function storeLogoUrl(Family $family, Collection $stores, int $storeId): ?string
    {
        return $stores->has($storeId)
            ? $this->entityMediaStorage->url($family, EntityMediaType::StoreLogo, $storeId)
            : null;
    }

    /**
     * @param  Collection<int, StoreSection>  $sections
     * @return array{sectionColour: string|null, sectionIcon: string|null, sectionIconUrl: string|null}
     */
    private function sectionVisuals(Family $family, Collection $sections, int $sectionId): array
    {
        $section = $sections->get($sectionId);
        if ( ! $section instanceof StoreSection) {
            return ['sectionColour' => null, 'sectionIcon' => null, 'sectionIconUrl' => null];
        }

        return [
            'sectionColour' => $section->colour,
            'sectionIcon' => $section->icon->value,
            'sectionIconUrl' => $this->entityMediaStorage->url($family, EntityMediaType::StoreSectionIcon, $sectionId),
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
            'package' => [
                'grams' => $line->ingredient->package->weightGrams,
                'millilitres' => $line->ingredient->package->volumeMillilitres,
                'piece' => $line->ingredient->package->pieceCount,
            ],
            'purchasePackages' => $line->purchasePackages,
            'quantities' => $quantities,
            'contributions' => array_map(fn (RecipeContribution $contribution, int $index): array => [
                'contributionKey' => implode(':', [
                    $contribution->recipeId,
                    $contribution->originalIngredientId,
                    $contribution->quantityKind->value,
                    $index,
                ]),
                'recipeId' => $contribution->recipeId,
                'recipeName' => $contribution->recipeName,
                'originalIngredientId' => $contribution->originalIngredientId,
                'originalIngredientName' => $contribution->originalIngredientName,
                'quantityKind' => $contribution->quantityKind->value,
                'required' => $this->quantity($contribution->required, $contribution->quantityKind),
                'packageFraction' => $contribution->packageFraction->fraction(),
            ], $line->contributions, array_keys($line->contributions)),
            'eligibleAlternatives' => array_map(static fn (AlternativeIngredientDefinition $alternative): array => [
                'ingredientId' => $alternative->id,
                'ingredientName' => $alternative->name,
            ], $line->eligibleAlternatives),
            'alternativeChoices' => array_map(static fn (AlternativeChoiceProvenance $choice): array => [
                'originalIngredientId' => $choice->originalIngredientId,
                'originalIngredientName' => $choice->originalIngredientName,
                'alternativeIngredientId' => $choice->alternativeIngredientId,
                'alternativeIngredientName' => $choice->alternativeIngredientName,
            ], $line->alternativeChoices),
        ];
    }

    /** @return array{exact: string, label: string, value: string, unit: string, approximate: bool} */
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
            'exact' => $quantity->fraction(),
            'label' => str_replace('.', ',', $normalized) . ' ' . $unit,
            'value' => $normalized,
            'unit' => $unit,
            'approximate' => BigRational::of($normalized)->compareTo($value) !== 0,
        ];
    }

    /** @return array<string, mixed> */
    private function problem(CalculationProblem $problem, int $index): array
    {
        return [
            'problemKey' => implode(':', [
                $problem->recipeId,
                $problem->ingredientId,
                $problem->unit,
                $problem->reason->value,
                $index,
            ]),
            'recipeId' => $problem->recipeId,
            'recipeName' => $problem->recipeName,
            'ingredientId' => $problem->ingredientId,
            'ingredientName' => $problem->ingredientName,
            'quantityLabel' => $this->problemQuantityLabel($problem->quantity, $problem->unit),
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

    private function problemQuantityLabel(string $quantity, string $unit): string
    {
        $unitLabel = match ($unit) {
            'servings' => 'porce',
            'grams' => 'g',
            'millilitres' => 'ml',
            'piece' => 'ks',
            default => 'jednotek',
        };

        return str_replace('.', ',', $this->normalizeDecimal($quantity)) . ' ' . $unitLabel;
    }

    private function normalizeDecimal(string $value): string
    {
        return str_contains($value, '.') ? rtrim(rtrim($value, '0'), '.') : $value;
    }
}
