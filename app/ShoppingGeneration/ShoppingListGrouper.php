<?php

declare(strict_types=1);

namespace App\ShoppingGeneration;

use App\ShoppingGeneration\Values\ShoppingList;
use App\ShoppingGeneration\Values\ShoppingListLine;
use App\ShoppingGeneration\Values\StoreGroup;
use App\ShoppingGeneration\Values\StoreReference;
use App\ShoppingGeneration\Values\StoreSectionGroup;
use App\ShoppingGeneration\Values\StoreSectionReference;

final class ShoppingListGrouper
{
    /** @param list<ShoppingListLine> $lines */
    public function group(array $lines): ShoppingList
    {
        /** @var array<int, array{store: StoreReference, sections: array<int, array{section: StoreSectionReference, lines: list<ShoppingListLine>}>, unsectioned: list<ShoppingListLine>}> $stores */
        $stores = [];
        $unplacedLines = [];

        foreach ($lines as $line) {
            $placement = $line->ingredient->placement;

            if ($placement === null) {
                $unplacedLines[] = $line;

                continue;
            }

            $storeId = $placement->store->id;
            $store = $stores[$storeId] ?? [
                'store' => $placement->store,
                'sections' => [],
                'unsectioned' => [],
            ];

            if ($placement->section === null) {
                $store['unsectioned'][] = $line;
            } else {
                $sectionId = $placement->section->id;
                $section = $store['sections'][$sectionId] ?? [
                    'section' => $placement->section,
                    'lines' => [],
                ];
                $section['lines'][] = $line;
                $store['sections'][$sectionId] = $section;
            }

            $stores[$storeId] = $store;
        }

        $storeGroups = array_map(function (array $store): StoreGroup {
            $sections = array_values($store['sections']);
            usort(
                $sections,
                static fn (array $left, array $right): int => [
                    $left['section']->position,
                    $left['section']->id,
                ] <=> [
                    $right['section']->position,
                    $right['section']->id,
                ],
            );

            $sectionGroups = array_map(fn (array $section): StoreSectionGroup => new StoreSectionGroup(
                section: $section['section'],
                lines: $this->sortedLines($section['lines']),
            ), $sections);

            return new StoreGroup(
                store: $store['store'],
                sections: $sectionGroups,
                unsectionedLines: $this->sortedLines($store['unsectioned']),
            );
        }, array_values($stores));

        usort(
            $storeGroups,
            static fn (StoreGroup $left, StoreGroup $right): int => NormalizedNameComparator::compare(
                $left->store->normalizedName,
                $left->store->id,
                $right->store->normalizedName,
                $right->store->id,
            ),
        );

        return new ShoppingList(
            storeGroups: $storeGroups,
            unplacedLines: $this->sortedLines($unplacedLines),
        );
    }

    /**
     * @param  list<ShoppingListLine>  $lines
     * @return list<ShoppingListLine>
     */
    private function sortedLines(array $lines): array
    {
        usort(
            $lines,
            static fn (ShoppingListLine $left, ShoppingListLine $right): int => NormalizedNameComparator::compare(
                $left->ingredient->normalizedName,
                $left->ingredient->id,
                $right->ingredient->normalizedName,
                $right->ingredient->id,
            ),
        );

        return $lines;
    }
}
