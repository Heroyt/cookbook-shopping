<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Snapshots;

use UnexpectedValueException;

/**
 * @phpstan-type QuantityKindV1 'grams'|'millilitres'|'piece'
 * @phpstan-type QuantityDisplayV1 array{exact: string, label: string, value: string, unit: string, approximate: bool}
 * @phpstan-type QuantityV1 array{kind: QuantityKindV1, required: QuantityDisplayV1, purchased: QuantityDisplayV1, surplus: QuantityDisplayV1}
 * @phpstan-type PackageV1 array{grams: string|null, millilitres: string|null, piece: string|null}
 * @phpstan-type ContributionV1 array{contributionKey: string, recipeId: int, recipeName: string, originalIngredientId: int, originalIngredientName: string, quantityKind: QuantityKindV1, required: QuantityDisplayV1, packageFraction: string}
 * @phpstan-type EligibleAlternativeV1 array{ingredientId: int, ingredientName: string}
 * @phpstan-type AlternativeChoiceV1 array{originalIngredientId: int, originalIngredientName: string, alternativeIngredientId: int, alternativeIngredientName: string}
 * @phpstan-type LineV1 array{ingredientId: int, ingredientName: string, package: PackageV1, purchasePackages: string, quantities: list<QuantityV1>, contributions: list<ContributionV1>, eligibleAlternatives: list<EligibleAlternativeV1>, alternativeChoices: list<AlternativeChoiceV1>}
 * @phpstan-type SectionV1 array{sectionId: int, sectionName: string, lines: list<LineV1>}
 * @phpstan-type StoreGroupV1 array{storeId: int, storeName: string, sections: list<SectionV1>, unsectionedLines: list<LineV1>}
 * @phpstan-type ShoppingListV1 array{storeGroups: list<StoreGroupV1>, unplacedLines: list<LineV1>}
 * @phpstan-type SimplePlanRecipeV1 array{recipeId: int, recipeName: string, servingCount: string, servingCountLabel: string}
 * @phpstan-type SimplePlanSourceV1 array{kind: 'simple_plan', recipes: list<SimplePlanRecipeV1>}
 * @phpstan-type CalendarSourceV1 array{kind: 'calendar', dates: list<string>, dateLabels: list<string>}
 * @phpstan-type SourceV1 SimplePlanSourceV1|CalendarSourceV1
 * @phpstan-type PayloadV1 array{locale: 'cs', source: SourceV1, appliedAlternatives: list<AlternativeChoiceV1>, shoppingList: ShoppingListV1}
 */
final class SavedShoppingListV1
{
    /**
     * @param  array<string, mixed>  $shoppingList
     * @param  array<string, mixed>  $source
     * @return PayloadV1
     */
    public function encode(array $shoppingList, array $source): array
    {
        $shoppingList = $this->shoppingList($shoppingList);

        return [
            'locale' => 'cs',
            'source' => $this->source($source),
            'appliedAlternatives' => $this->appliedAlternatives($shoppingList),
            'shoppingList' => $shoppingList,
        ];
    }

    /** @return PayloadV1 */
    public function decode(mixed $payload): array
    {
        $payload = $this->object($payload);
        if ($this->string($payload, 'locale') !== 'cs') {
            throw new UnexpectedValueException('Saved Shopping List v1 has an unsupported locale.');
        }

        return [
            'locale' => 'cs',
            'source' => $this->source($payload['source'] ?? null),
            'appliedAlternatives' => $this->alternativeChoices($payload['appliedAlternatives'] ?? null),
            'shoppingList' => $this->shoppingList($payload['shoppingList'] ?? null),
        ];
    }

    /** @return SourceV1 */
    private function source(mixed $value): array
    {
        $source = $this->object($value);
        $kind = $this->string($source, 'kind');

        if ($kind === 'simple_plan') {
            $recipes = [];
            foreach ($this->list($source['recipes'] ?? null) as $recipe) {
                $recipe = $this->object($recipe);
                $recipes[] = [
                    'recipeId' => $this->integer($recipe, 'recipeId'),
                    'recipeName' => $this->string($recipe, 'recipeName'),
                    'servingCount' => $this->string($recipe, 'servingCount'),
                    'servingCountLabel' => $this->string($recipe, 'servingCountLabel'),
                ];
            }

            return ['kind' => $kind, 'recipes' => $recipes];
        }

        if ($kind === 'calendar') {
            $dates = $this->strings($source['dates'] ?? null);
            $dateLabels = $this->strings($source['dateLabels'] ?? null);
            if (count($dates) !== count($dateLabels)) {
                throw new UnexpectedValueException('Saved Shopping List v1 Calendar provenance is incomplete.');
            }

            return ['kind' => $kind, 'dates' => $dates, 'dateLabels' => $dateLabels];
        }

        throw new UnexpectedValueException('Saved Shopping List v1 has an invalid source kind.');
    }

    /** @return ShoppingListV1 */
    private function shoppingList(mixed $value): array
    {
        $shoppingList = $this->object($value);
        $storeGroups = [];
        foreach ($this->list($shoppingList['storeGroups'] ?? null) as $storeGroup) {
            $storeGroups[] = $this->storeGroup($storeGroup);
        }

        return [
            'storeGroups' => $storeGroups,
            'unplacedLines' => $this->lines($shoppingList['unplacedLines'] ?? null),
        ];
    }

    /** @return StoreGroupV1 */
    private function storeGroup(mixed $value): array
    {
        $storeGroup = $this->object($value);
        $sections = [];
        foreach ($this->list($storeGroup['sections'] ?? null) as $section) {
            $section = $this->object($section);
            $sections[] = [
                'sectionId' => $this->integer($section, 'sectionId'),
                'sectionName' => $this->string($section, 'sectionName'),
                'lines' => $this->lines($section['lines'] ?? null),
            ];
        }

        return [
            'storeId' => $this->integer($storeGroup, 'storeId'),
            'storeName' => $this->string($storeGroup, 'storeName'),
            'sections' => $sections,
            'unsectionedLines' => $this->lines($storeGroup['unsectionedLines'] ?? null),
        ];
    }

    /** @return list<LineV1> */
    private function lines(mixed $value): array
    {
        $lines = [];
        foreach ($this->list($value) as $line) {
            $lines[] = $this->line($line);
        }

        return $lines;
    }

    /** @return LineV1 */
    private function line(mixed $value): array
    {
        $line = $this->object($value);
        $package = $this->object($line['package'] ?? null);

        return [
            'ingredientId' => $this->integer($line, 'ingredientId'),
            'ingredientName' => $this->string($line, 'ingredientName'),
            'package' => [
                'grams' => $this->nullableString($package, 'grams'),
                'millilitres' => $this->nullableString($package, 'millilitres'),
                'piece' => $this->nullableString($package, 'piece'),
            ],
            'purchasePackages' => $this->string($line, 'purchasePackages'),
            'quantities' => $this->quantities($line['quantities'] ?? null),
            'contributions' => $this->contributions($line['contributions'] ?? null),
            'eligibleAlternatives' => $this->eligibleAlternatives($line['eligibleAlternatives'] ?? null),
            'alternativeChoices' => $this->alternativeChoices($line['alternativeChoices'] ?? null),
        ];
    }

    /** @return list<QuantityV1> */
    private function quantities(mixed $value): array
    {
        $quantities = [];
        foreach ($this->list($value) as $quantity) {
            $quantity = $this->object($quantity);
            $kind = $this->quantityKind($quantity);
            $quantities[] = [
                'kind' => $kind,
                'required' => $this->quantityDisplay($quantity['required'] ?? null),
                'purchased' => $this->quantityDisplay($quantity['purchased'] ?? null),
                'surplus' => $this->quantityDisplay($quantity['surplus'] ?? null),
            ];
        }

        return $quantities;
    }

    /** @return QuantityDisplayV1 */
    private function quantityDisplay(mixed $value): array
    {
        $quantity = $this->object($value);

        return [
            'exact' => $this->string($quantity, 'exact'),
            'label' => $this->string($quantity, 'label'),
            'value' => $this->string($quantity, 'value'),
            'unit' => $this->string($quantity, 'unit'),
            'approximate' => $this->boolean($quantity, 'approximate'),
        ];
    }

    /** @return list<ContributionV1> */
    private function contributions(mixed $value): array
    {
        $contributions = [];
        foreach ($this->list($value) as $contribution) {
            $contribution = $this->object($contribution);
            $contributions[] = [
                'contributionKey' => $this->string($contribution, 'contributionKey'),
                'recipeId' => $this->integer($contribution, 'recipeId'),
                'recipeName' => $this->string($contribution, 'recipeName'),
                'originalIngredientId' => $this->integer($contribution, 'originalIngredientId'),
                'originalIngredientName' => $this->string($contribution, 'originalIngredientName'),
                'quantityKind' => $this->quantityKind($contribution, 'quantityKind'),
                'required' => $this->quantityDisplay($contribution['required'] ?? null),
                'packageFraction' => $this->string($contribution, 'packageFraction'),
            ];
        }

        return $contributions;
    }

    /** @return list<EligibleAlternativeV1> */
    private function eligibleAlternatives(mixed $value): array
    {
        $alternatives = [];
        foreach ($this->list($value) as $alternative) {
            $alternative = $this->object($alternative);
            $alternatives[] = [
                'ingredientId' => $this->integer($alternative, 'ingredientId'),
                'ingredientName' => $this->string($alternative, 'ingredientName'),
            ];
        }

        return $alternatives;
    }

    /** @return list<AlternativeChoiceV1> */
    private function alternativeChoices(mixed $value): array
    {
        $choices = [];
        foreach ($this->list($value) as $choice) {
            $choice = $this->object($choice);
            $choices[] = [
                'originalIngredientId' => $this->integer($choice, 'originalIngredientId'),
                'originalIngredientName' => $this->string($choice, 'originalIngredientName'),
                'alternativeIngredientId' => $this->integer($choice, 'alternativeIngredientId'),
                'alternativeIngredientName' => $this->string($choice, 'alternativeIngredientName'),
            ];
        }

        return $choices;
    }

    /**
     * @param  ShoppingListV1  $shoppingList
     * @return list<AlternativeChoiceV1>
     */
    private function appliedAlternatives(array $shoppingList): array
    {
        $choices = [];
        foreach ($this->shoppingListLines($shoppingList) as $line) {
            foreach ($this->alternativeChoices($line['alternativeChoices']) as $choice) {
                $choices[$choice['originalIngredientId']] = $choice;
            }
        }

        return array_values($choices);
    }

    /**
     * @param  ShoppingListV1  $shoppingList
     * @return list<LineV1>
     */
    private function shoppingListLines(array $shoppingList): array
    {
        $lines = [];
        foreach ($shoppingList['storeGroups'] as $storeGroup) {
            $storeGroup = $this->object($storeGroup);
            foreach ($this->list($storeGroup['sections'] ?? null) as $section) {
                $section = $this->object($section);
                $lines = [...$lines, ...$this->lines($section['lines'] ?? null)];
            }
            $lines = [...$lines, ...$this->lines($storeGroup['unsectionedLines'] ?? null)];
        }

        return [...$lines, ...$shoppingList['unplacedLines']];
    }

    /** @return array<string, mixed> */
    private function object(mixed $value): array
    {
        if ( ! is_array($value)) {
            throw new UnexpectedValueException('Saved Shopping List v1 expected an object.');
        }

        $object = [];
        foreach ($value as $key => $item) {
            if ( ! is_string($key)) {
                throw new UnexpectedValueException('Saved Shopping List v1 expected string object keys.');
            }
            $object[$key] = $item;
        }

        return $object;
    }

    /** @return list<mixed> */
    private function list(mixed $value): array
    {
        if ( ! is_array($value) || ! array_is_list($value)) {
            throw new UnexpectedValueException('Saved Shopping List v1 expected a list.');
        }

        return $value;
    }

    /** @return list<string> */
    private function strings(mixed $value): array
    {
        $strings = [];
        foreach ($this->list($value) as $item) {
            if ( ! is_string($item)) {
                throw new UnexpectedValueException('Saved Shopping List v1 expected a string list.');
            }
            $strings[] = $item;
        }

        return $strings;
    }

    /** @param array<string, mixed> $object */
    private function string(array $object, string $key): string
    {
        $value = $object[$key] ?? null;
        if ( ! is_string($value)) {
            throw new UnexpectedValueException("Saved Shopping List v1 expected string field {$key}.");
        }

        return $value;
    }

    /** @param array<string, mixed> $object */
    private function nullableString(array $object, string $key): ?string
    {
        $value = $object[$key] ?? null;
        if ($value !== null && ! is_string($value)) {
            throw new UnexpectedValueException("Saved Shopping List v1 expected nullable string field {$key}.");
        }

        return $value;
    }

    /** @param array<string, mixed> $object */
    private function integer(array $object, string $key): int
    {
        $value = $object[$key] ?? null;
        if ( ! is_int($value)) {
            throw new UnexpectedValueException("Saved Shopping List v1 expected integer field {$key}.");
        }

        return $value;
    }

    /** @param array<string, mixed> $object */
    private function boolean(array $object, string $key): bool
    {
        $value = $object[$key] ?? null;
        if ( ! is_bool($value)) {
            throw new UnexpectedValueException("Saved Shopping List v1 expected boolean field {$key}.");
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $object
     * @return QuantityKindV1
     */
    private function quantityKind(array $object, string $key = 'kind'): string
    {
        $kind = $this->string($object, $key);
        if ( ! in_array($kind, ['grams', 'millilitres', 'piece'], true)) {
            throw new UnexpectedValueException("Saved Shopping List v1 expected quantity kind field {$key}.");
        }

        return $kind;
    }
}
