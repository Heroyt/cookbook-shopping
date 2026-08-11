<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Snapshots;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * @phpstan-import-type PayloadV1 from SavedShoppingListV1
 * @phpstan-import-type SourceV1 from SavedShoppingListV1
 *
 * @phpstan-type GeneratedPresentation array{shoppingList?: mixed, problems?: mixed}
 */
final class SavedShoppingListPayload
{
    public const int SCHEMA_VERSION = 1;

    public function __construct(private readonly SavedShoppingListV1 $v1) {}

    /**
     * @param  GeneratedPresentation  $presentation
     * @param  array<int, string>  $selections
     * @return PayloadV1
     */
    public function forSimplePlan(array $presentation, array $selections): array
    {
        $shoppingList = $this->shoppingList($presentation);
        $recipeNames = $this->recipeNames($shoppingList);
        $recipes = [];
        foreach ($selections as $recipeId => $servingCount) {
            $recipeName = $recipeNames[$recipeId] ?? null;
            if ( ! is_string($recipeName)) {
                throw new InvalidArgumentException('The generated Shopping List is missing Simple Plan provenance.');
            }
            $recipes[] = [
                'recipeId' => $recipeId,
                'recipeName' => $recipeName,
                'servingCount' => $servingCount,
                'servingCountLabel' => trans_choice('Shopping List serving count', (float) $servingCount, [
                    'count' => str_replace('.', ',', $servingCount),
                ]),
            ];
        }

        return $this->payload($shoppingList, [
            'kind' => 'simple_plan',
            'recipes' => $recipes,
        ]);
    }

    /**
     * @param  GeneratedPresentation  $presentation
     * @param  list<string>  $dates
     * @return PayloadV1
     */
    public function forCalendar(array $presentation, array $dates): array
    {
        return $this->payload($this->shoppingList($presentation), [
            'kind' => 'calendar',
            'dates' => $dates,
            'dateLabels' => array_map(
                static fn (string $date): string => CarbonImmutable::parse($date)->format('j. n. Y'),
                $dates,
            ),
        ]);
    }

    /**
     * @param  array<string, mixed>  $shoppingList
     * @param  SourceV1  $source
     * @return PayloadV1
     */
    private function payload(array $shoppingList, array $source): array
    {
        return $this->v1->encode($shoppingList, $source);
    }

    /**
     * @param  GeneratedPresentation  $presentation
     * @return array<string, mixed>
     */
    private function shoppingList(array $presentation): array
    {
        if (array_key_exists('shoppingList', $presentation) && $presentation['shoppingList'] === null) {
            throw new InvalidArgumentException('The Shopping List has Calculation Problems.');
        }

        $shoppingList = $this->stringKeyedArray($presentation['shoppingList'] ?? null);
        if ($shoppingList === null) {
            throw new InvalidArgumentException('No complete generated Shopping List is available.');
        }

        return $shoppingList;
    }

    /**
     * @param  array<string, mixed>  $shoppingList
     * @return array<int, string>
     */
    private function recipeNames(array $shoppingList): array
    {
        $names = [];
        foreach ($this->lines($shoppingList) as $line) {
            $contributions = $line['contributions'] ?? [];
            if ( ! is_array($contributions)) {
                continue;
            }
            foreach ($contributions as $contribution) {
                $contribution = $this->stringKeyedArray($contribution);
                if ($contribution === null || ! is_numeric($contribution['recipeId'] ?? null) || ! is_string($contribution['recipeName'] ?? null)) {
                    continue;
                }
                $names[(int) $contribution['recipeId']] = $contribution['recipeName'];
            }
        }

        return $names;
    }

    /**
     * @param  array<string, mixed>  $shoppingList
     * @return list<array<string, mixed>>
     */
    private function lines(array $shoppingList): array
    {
        $lines = [];
        $storeGroups = $shoppingList['storeGroups'] ?? [];
        if (is_array($storeGroups)) {
            foreach ($storeGroups as $storeGroup) {
                if ( ! is_array($storeGroup)) {
                    continue;
                }
                $sections = $storeGroup['sections'] ?? [];
                if (is_array($sections)) {
                    foreach ($sections as $section) {
                        if (is_array($section)) {
                            $lines = [...$lines, ...$this->arrayLines($section['lines'] ?? [])];
                        }
                    }
                }
                $lines = [...$lines, ...$this->arrayLines($storeGroup['unsectionedLines'] ?? [])];
            }
        }

        return [...$lines, ...$this->arrayLines($shoppingList['unplacedLines'] ?? [])];
    }

    /** @return list<array<string, mixed>> */
    private function arrayLines(mixed $value): array
    {
        if ( ! is_array($value)) {
            return [];
        }

        $lines = [];
        foreach ($value as $line) {
            $line = $this->stringKeyedArray($line);
            if ($line !== null) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    /** @return array<string, mixed>|null */
    private function stringKeyedArray(mixed $value): ?array
    {
        if ( ! is_array($value)) {
            return null;
        }

        $items = [];
        foreach ($value as $key => $item) {
            if ( ! is_string($key)) {
                return null;
            }
            $items[$key] = $item;
        }

        return $items;
    }
}
