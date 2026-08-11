<?php

declare(strict_types=1);

namespace Tests\Unit\ShoppingGeneration;

use App\ShoppingGeneration\ShoppingListCalculator;
use App\ShoppingGeneration\ShoppingListGenerator;
use App\ShoppingGeneration\ShoppingListGrouper;
use App\ShoppingGeneration\Values\AlternativeChoice;
use App\ShoppingGeneration\Values\AlternativeIngredientDefinition;
use App\ShoppingGeneration\Values\CalculationProblemReason;
use App\ShoppingGeneration\Values\GenerationRequest;
use App\ShoppingGeneration\Values\IngredientDefinition;
use App\ShoppingGeneration\Values\IngredientPackage;
use App\ShoppingGeneration\Values\IngredientPlacement;
use App\ShoppingGeneration\Values\QuantityKind;
use App\ShoppingGeneration\Values\RecipeIngredientInput;
use App\ShoppingGeneration\Values\RecipeSelection;
use App\ShoppingGeneration\Values\ShoppingList;
use App\ShoppingGeneration\Values\StoreReference;
use App\ShoppingGeneration\Values\StoreSectionReference;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ShoppingListGeneratorTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function invalidAlternativeChoices(): array
    {
        return [
            'inactive' => ['inactive'],
            'missing a required canonical kind' => ['missing-kind'],
            'not a direct edge' => ['indirect'],
        ];
    }

    public function test_it_aggregates_globally_before_rounding_and_retains_recipe_contributions(): void
    {
        $flour = new IngredientDefinition(
            id: 11,
            name: 'Mouka',
            normalizedName: 'mouka',
            package: new IngredientPackage(weightGrams: '150.000000'),
        );
        $request = new GenerationRequest([
            new RecipeSelection(
                recipeId: 21,
                recipeName: 'Chléb',
                baseServings: '2.000000',
                requestedServings: '2.000000',
                ingredients: [new RecipeIngredientInput($flour, '70.000000', QuantityKind::Grams)],
            ),
            new RecipeSelection(
                recipeId: 22,
                recipeName: 'Koláč',
                baseServings: '4.000000',
                requestedServings: '4.000000',
                ingredients: [new RecipeIngredientInput($flour, '70.000000', QuantityKind::Grams)],
            ),
        ]);

        $result = (new ShoppingListGenerator(new ShoppingListCalculator(), new ShoppingListGrouper()))->generate($request);

        self::assertTrue($result->isSuccessful());
        self::assertNotNull($result->shoppingList);
        self::assertSame([], $result->problems);
        self::assertCount(1, $result->shoppingList->unplacedLines);
        $line = $result->shoppingList->unplacedLines[0];
        self::assertSame(11, $line->ingredient->id);
        self::assertSame('1', $line->purchasePackages);
        self::assertSame('140', $line->quantity(QuantityKind::Grams)->required->fraction());
        self::assertSame('150', $line->quantity(QuantityKind::Grams)->purchased->fraction());
        self::assertSame('10', $line->quantity(QuantityKind::Grams)->surplus->fraction());
        self::assertSame(
            [
                ['recipeId' => 21, 'recipeName' => 'Chléb', 'required' => '70'],
                ['recipeId' => 22, 'recipeName' => 'Koláč', 'required' => '70'],
            ],
            array_map(
                static fn ($contribution): array => [
                    'recipeId' => $contribution->recipeId,
                    'recipeName' => $contribution->recipeName,
                    'required' => $contribution->required->fraction(),
                ],
                $line->contributions,
            ),
        );
    }

    public function test_it_scales_fractional_servings_and_combines_repeated_metric_and_piece_lines_exactly(): void
    {
        $tomatoes = new IngredientDefinition(
            id: 12,
            name: 'Rajčata',
            normalizedName: 'rajčata',
            package: new IngredientPackage(weightGrams: '500.000000', pieceCount: '10.000000'),
        );
        $request = new GenerationRequest([
            new RecipeSelection(
                recipeId: 23,
                recipeName: 'Salát',
                baseServings: '4.000000',
                requestedServings: '1.500000',
                ingredients: [
                    new RecipeIngredientInput($tomatoes, '100.000000', QuantityKind::Grams),
                    new RecipeIngredientInput($tomatoes, '2.000000', QuantityKind::Piece),
                ],
            ),
        ]);

        $result = (new ShoppingListGenerator(new ShoppingListCalculator(), new ShoppingListGrouper()))->generate($request);

        self::assertNotNull($result->shoppingList);
        $line = $result->shoppingList->unplacedLines[0];
        self::assertSame('1', $line->purchasePackages);
        self::assertSame('75', $line->quantity(QuantityKind::Grams)->required->fraction());
        self::assertSame('500', $line->quantity(QuantityKind::Grams)->purchased->fraction());
        self::assertSame('425', $line->quantity(QuantityKind::Grams)->surplus->fraction());
        self::assertSame('3/2', $line->quantity(QuantityKind::Piece)->required->fraction());
        self::assertSame('10', $line->quantity(QuantityKind::Piece)->purchased->fraction());
        self::assertSame('17/2', $line->quantity(QuantityKind::Piece)->surplus->fraction());
        self::assertCount(2, $line->contributions);
    }

    public function test_it_collects_every_recoverable_problem_without_returning_partial_output(): void
    {
        $weightOnly = new IngredientDefinition(
            id: 31,
            name: 'Mouka',
            normalizedName: 'mouka',
            package: new IngredientPackage(weightGrams: '500.000000'),
        );
        $invalidPackage = new IngredientDefinition(
            id: 32,
            name: 'Neplatné balení',
            normalizedName: 'neplatné balení',
            package: new IngredientPackage(weightGrams: '100.000000', volumeMillilitres: '100.000000'),
        );
        $request = new GenerationRequest([
            new RecipeSelection(
                recipeId: 41,
                recipeName: 'Nulová porce',
                baseServings: '2.000000',
                requestedServings: '0.000000',
                ingredients: [new RecipeIngredientInput($weightOnly, '50.000000', QuantityKind::Grams)],
            ),
            new RecipeSelection(
                recipeId: 42,
                recipeName: 'Chybějící objem',
                baseServings: '2.000000',
                requestedServings: '2.000000',
                ingredients: [new RecipeIngredientInput($weightOnly, '50.000000', QuantityKind::Millilitres)],
            ),
            new RecipeSelection(
                recipeId: 43,
                recipeName: 'Dvojí metrika',
                baseServings: '2.000000',
                requestedServings: '2.000000',
                ingredients: [new RecipeIngredientInput($invalidPackage, '50.000000', QuantityKind::Grams)],
            ),
        ]);

        $result = (new ShoppingListGenerator(new ShoppingListCalculator(), new ShoppingListGrouper()))->generate($request);

        self::assertFalse($result->isSuccessful());
        self::assertNull($result->shoppingList);
        self::assertSame(
            [
                [41, 31, '0.000000', 'servings', CalculationProblemReason::NonPositiveRequestedServings],
                [42, 31, '50.000000', 'millilitres', CalculationProblemReason::MissingPackageQuantity],
                [43, 32, '50.000000', 'grams', CalculationProblemReason::InvalidPackageDefinition],
            ],
            array_map(
                static fn ($problem): array => [
                    $problem->recipeId,
                    $problem->ingredientId,
                    $problem->quantity,
                    $problem->unit,
                    $problem->reason,
                ],
                $result->problems,
            ),
        );
    }

    public function test_it_offers_only_active_direct_kind_compatible_alternatives_and_applies_one_choice(): void
    {
        $replacement = new AlternativeIngredientDefinition(
            id: 52,
            name: 'Malé rajče',
            normalizedName: 'malé rajče',
            package: new IngredientPackage(weightGrams: '200.000000', pieceCount: '4.000000'),
            active: true,
        );
        $tomatoes = new IngredientDefinition(
            id: 51,
            name: 'Velké rajče',
            normalizedName: 'velké rajče',
            package: new IngredientPackage(weightGrams: '500.000000', pieceCount: '10.000000'),
            alternatives: [
                $replacement,
                new AlternativeIngredientDefinition(
                    id: 53,
                    name: 'Rajčata bez kusů',
                    normalizedName: 'rajčata bez kusů',
                    package: new IngredientPackage(weightGrams: '200.000000'),
                    active: true,
                ),
                new AlternativeIngredientDefinition(
                    id: 54,
                    name: 'Archivované rajče',
                    normalizedName: 'archivované rajče',
                    package: new IngredientPackage(weightGrams: '200.000000', pieceCount: '4.000000'),
                    active: false,
                ),
            ],
        );
        $selection = new RecipeSelection(
            recipeId: 55,
            recipeName: 'Rajčatový salát',
            baseServings: '2.000000',
            requestedServings: '2.000000',
            ingredients: [
                new RecipeIngredientInput($tomatoes, '250.000000', QuantityKind::Grams),
                new RecipeIngredientInput($tomatoes, '2.000000', QuantityKind::Piece),
            ],
        );
        $generator = new ShoppingListGenerator(new ShoppingListCalculator(), new ShoppingListGrouper());

        $initial = $generator->generate(new GenerationRequest([$selection]));

        self::assertNotNull($initial->shoppingList);
        self::assertSame(
            [52],
            array_map(static fn ($alternative): int => $alternative->id, $initial->shoppingList->unplacedLines[0]->eligibleAlternatives),
        );

        $replaced = $generator->generate(new GenerationRequest(
            [$selection],
            [new AlternativeChoice(originalIngredientId: 51, alternativeIngredientId: 52)],
        ));

        self::assertNotNull($replaced->shoppingList);
        self::assertCount(1, $replaced->shoppingList->unplacedLines);
        $line = $replaced->shoppingList->unplacedLines[0];
        self::assertSame(52, $line->ingredient->id);
        self::assertSame('2', $line->purchasePackages);
        self::assertSame('350', $line->quantity(QuantityKind::Grams)->required->fraction());
        self::assertSame('400', $line->quantity(QuantityKind::Grams)->purchased->fraction());
        self::assertSame('50', $line->quantity(QuantityKind::Grams)->surplus->fraction());
        self::assertSame('7', $line->quantity(QuantityKind::Piece)->required->fraction());
        self::assertSame([], $line->eligibleAlternatives);
        self::assertSame(
            [[51, 'Velké rajče', 52, 'Malé rajče']],
            array_map(
                static fn ($choice): array => [
                    $choice->originalIngredientId,
                    $choice->originalIngredientName,
                    $choice->alternativeIngredientId,
                    $choice->alternativeIngredientName,
                ],
                $line->alternativeChoices,
            ),
        );
    }

    public function test_grouping_is_deterministic_by_normalized_names_stable_ids_and_section_traversal(): void
    {
        $albert = new StoreReference(1, 'Albert', 'albert');
        $zabka = new StoreReference(2, 'Žabka', 'žabka');
        $pasta = new StoreSectionReference(102, 'Těstoviny', 1);
        $produce = new StoreSectionReference(101, 'Zelenina', 2);
        $ingredients = [
            new IngredientDefinition(70, 'Caj B', 'caj', new IngredientPackage(pieceCount: '1'), new IngredientPlacement($albert, $produce)),
            new IngredientDefinition(69, 'Caj A', 'caj', new IngredientPackage(pieceCount: '1'), new IngredientPlacement($albert, $produce)),
            new IngredientDefinition(71, 'Čaj', 'čaj', new IngredientPackage(pieceCount: '1'), new IngredientPlacement($albert, $produce)),
            new IngredientDefinition(68, 'Nudle', 'nudle', new IngredientPackage(pieceCount: '1'), new IngredientPlacement($albert, $pasta)),
            new IngredientDefinition(72, 'Taška', 'taška', new IngredientPackage(pieceCount: '1'), new IngredientPlacement($albert)),
            new IngredientDefinition(73, 'Pečivo', 'pečivo', new IngredientPackage(pieceCount: '1'), new IngredientPlacement($zabka)),
            new IngredientDefinition(74, 'Sůl', 'sůl', new IngredientPackage(pieceCount: '1')),
        ];
        $generator = new ShoppingListGenerator(new ShoppingListCalculator(), new ShoppingListGrouper());

        $forward = $generator->generate(new GenerationRequest([
            $this->selectionWithIngredients($ingredients),
        ]));
        $reverse = $generator->generate(new GenerationRequest([
            $this->selectionWithIngredients(array_reverse($ingredients)),
        ]));

        self::assertNotNull($forward->shoppingList);
        self::assertNotNull($reverse->shoppingList);
        $expected = [
            'stores' => [
                [
                    'id' => 1,
                    'sections' => [
                        ['id' => 102, 'ingredients' => [68]],
                        ['id' => 101, 'ingredients' => [69, 70, 71]],
                    ],
                    'unsectioned' => [72],
                ],
                ['id' => 2, 'sections' => [], 'unsectioned' => [73]],
            ],
            'unplaced' => [74],
        ];
        self::assertSame($expected, $this->groupingSnapshot($forward->shoppingList));
        self::assertSame($expected, $this->groupingSnapshot($reverse->shoppingList));
    }

    public function test_alternatives_reaggregate_globally_before_rounding_and_preserve_each_choice_and_contribution(): void
    {
        $tesco = new StoreReference(3, 'Tesco', 'tesco');
        $replacement = new AlternativeIngredientDefinition(
            id: 90,
            name: 'Společná alternativa',
            normalizedName: 'společná alternativa',
            package: new IngredientPackage(weightGrams: '100.000000'),
            active: true,
            placement: new IngredientPlacement($tesco),
        );
        $first = new IngredientDefinition(
            id: 81,
            name: 'První surovina',
            normalizedName: 'první surovina',
            package: new IngredientPackage(weightGrams: '150.000000'),
            alternatives: [$replacement],
        );
        $second = new IngredientDefinition(
            id: 82,
            name: 'Druhá surovina',
            normalizedName: 'druhá surovina',
            package: new IngredientPackage(weightGrams: '200.000000'),
            alternatives: [$replacement],
        );
        $request = new GenerationRequest(
            [new RecipeSelection(
                recipeId: 91,
                recipeName: 'Sloučený recept',
                baseServings: '1',
                requestedServings: '1',
                ingredients: [
                    new RecipeIngredientInput($second, '60', QuantityKind::Grams),
                    new RecipeIngredientInput($first, '60', QuantityKind::Grams),
                ],
            )],
            [
                new AlternativeChoice(82, 90),
                new AlternativeChoice(81, 90),
            ],
        );

        $result = (new ShoppingListGenerator(new ShoppingListCalculator(), new ShoppingListGrouper()))->generate($request);

        self::assertNotNull($result->shoppingList);
        self::assertSame([], $result->shoppingList->unplacedLines);
        self::assertCount(1, $result->shoppingList->storeGroups);
        $line = $result->shoppingList->storeGroups[0]->unsectionedLines[0];
        self::assertSame(90, $line->ingredient->id);
        self::assertSame(3, $line->ingredient->placement?->store->id);
        self::assertSame('2', $line->purchasePackages);
        self::assertSame('120', $line->quantity(QuantityKind::Grams)->required->fraction());
        self::assertSame('200', $line->quantity(QuantityKind::Grams)->purchased->fraction());
        self::assertSame('80', $line->quantity(QuantityKind::Grams)->surplus->fraction());
        self::assertSame(
            [[81, 90], [82, 90]],
            array_map(
                static fn ($choice): array => [$choice->originalIngredientId, $choice->alternativeIngredientId],
                $line->alternativeChoices,
            ),
        );
        self::assertSame(
            [[81, 'První surovina'], [82, 'Druhá surovina']],
            array_map(
                static fn ($contribution): array => [$contribution->originalIngredientId, $contribution->originalIngredientName],
                $line->contributions,
            ),
        );
    }

    #[DataProvider('invalidAlternativeChoices')]
    public function test_it_rejects_alternative_choices_that_are_not_direct_active_and_kind_compatible(string $case): void
    {
        $compatible = new IngredientPackage(weightGrams: '100', pieceCount: '2');
        $alternative = match ($case) {
            'inactive' => new AlternativeIngredientDefinition(102, 'Neaktivní', 'neaktivní', $compatible, false),
            'missing-kind' => new AlternativeIngredientDefinition(
                102,
                'Bez kusů',
                'bez kusů',
                new IngredientPackage(weightGrams: '100'),
                true,
            ),
            default => new AlternativeIngredientDefinition(102, 'Přímá', 'přímá', $compatible, true),
        };
        $original = new IngredientDefinition(
            id: 101,
            name: 'Původní',
            normalizedName: 'původní',
            package: $compatible,
            alternatives: [$alternative],
        );
        $chosenId = $case === 'indirect' ? 103 : 102;
        $request = new GenerationRequest(
            [new RecipeSelection(
                recipeId: 104,
                recipeName: 'Volba alternativy',
                baseServings: '1',
                requestedServings: '1',
                ingredients: [
                    new RecipeIngredientInput($original, '50', QuantityKind::Grams),
                    new RecipeIngredientInput($original, '1', QuantityKind::Piece),
                ],
            )],
            [new AlternativeChoice(101, $chosenId)],
        );

        $this->expectException(InvalidArgumentException::class);

        (new ShoppingListGenerator(new ShoppingListCalculator(), new ShoppingListGrouper()))->generate($request);
    }

    public function test_it_calculates_volume_with_exact_fractional_servings(): void
    {
        $milk = new IngredientDefinition(
            id: 110,
            name: 'Mléko',
            normalizedName: 'mléko',
            package: new IngredientPackage(volumeMillilitres: '750.000000'),
        );
        $request = new GenerationRequest([new RecipeSelection(
            recipeId: 111,
            recipeName: 'Kaše',
            baseServings: '3.000000',
            requestedServings: '2.500000',
            ingredients: [new RecipeIngredientInput($milk, '120.000000', QuantityKind::Millilitres)],
        )]);

        $result = (new ShoppingListGenerator(new ShoppingListCalculator(), new ShoppingListGrouper()))->generate($request);

        self::assertNotNull($result->shoppingList);
        $quantity = $result->shoppingList->unplacedLines[0]->quantity(QuantityKind::Millilitres);
        self::assertSame('100', $quantity->required->fraction());
        self::assertSame('750', $quantity->purchased->fraction());
        self::assertSame('650', $quantity->surplus->fraction());
    }

    public function test_it_collects_non_positive_over_scale_and_empty_package_problems(): void
    {
        $validPackage = new IngredientPackage(weightGrams: '500');
        $ingredient = new IngredientDefinition(120, 'Mouka', 'mouka', $validPackage);
        $invalidPackageIngredient = new IngredientDefinition(121, 'Bez balení', 'bez balení', new IngredientPackage());
        $request = new GenerationRequest([
            new RecipeSelection(122, 'Neplatný základ', '0', '1', [
                new RecipeIngredientInput($ingredient, '10', QuantityKind::Grams),
            ]),
            new RecipeSelection(123, 'Neplatné množství', '1', '1', [
                new RecipeIngredientInput($ingredient, '-1', QuantityKind::Grams),
            ]),
            new RecipeSelection(124, 'Příliš přesné množství', '1', '1', [
                new RecipeIngredientInput($ingredient, '1.0000001', QuantityKind::Grams),
            ]),
            new RecipeSelection(125, 'Prázdné balení', '1', '1', [
                new RecipeIngredientInput($invalidPackageIngredient, '1', QuantityKind::Piece),
            ]),
            new RecipeSelection(126, 'Dvě chyby porcí', '0', '1.0000001', [
                new RecipeIngredientInput($ingredient, '1', QuantityKind::Grams),
            ]),
        ]);

        $result = (new ShoppingListGenerator(new ShoppingListCalculator(), new ShoppingListGrouper()))->generate($request);

        self::assertNull($result->shoppingList);
        self::assertSame(
            [
                CalculationProblemReason::NonPositiveBaseServings,
                CalculationProblemReason::NonPositiveRecipeQuantity,
                CalculationProblemReason::InvalidRecipeQuantity,
                CalculationProblemReason::InvalidPackageDefinition,
                CalculationProblemReason::InvalidRequestedServings,
                CalculationProblemReason::NonPositiveBaseServings,
            ],
            array_map(static fn ($problem): CalculationProblemReason => $problem->reason, $result->problems),
        );
    }

    /** @param list<IngredientDefinition> $ingredients */
    private function selectionWithIngredients(array $ingredients): RecipeSelection
    {
        return new RecipeSelection(
            recipeId: 80,
            recipeName: 'Nákup',
            baseServings: '1',
            requestedServings: '1',
            ingredients: array_map(
                static fn (IngredientDefinition $ingredient): RecipeIngredientInput => new RecipeIngredientInput(
                    $ingredient,
                    '1',
                    QuantityKind::Piece,
                ),
                $ingredients,
            ),
        );
    }

    /** @return array<string, mixed> */
    private function groupingSnapshot(ShoppingList $shoppingList): array
    {
        return [
            'stores' => array_map(
                static fn ($storeGroup): array => [
                    'id' => $storeGroup->store->id,
                    'sections' => array_map(
                        static fn ($sectionGroup): array => [
                            'id' => $sectionGroup->section->id,
                            'ingredients' => array_map(static fn ($line): int => $line->ingredient->id, $sectionGroup->lines),
                        ],
                        $storeGroup->sections,
                    ),
                    'unsectioned' => array_map(static fn ($line): int => $line->ingredient->id, $storeGroup->unsectionedLines),
                ],
                $shoppingList->storeGroups,
            ),
            'unplaced' => array_map(static fn ($line): int => $line->ingredient->id, $shoppingList->unplacedLines),
        ];
    }
}
