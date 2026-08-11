<?php

declare(strict_types=1);

namespace App\MealPlanning\Values;

final readonly class SimplePlan
{
    /** @param array<int, ServingCount> $selections */
    private function __construct(private array $selections) {}

    /** @param array<array-key, mixed> $selections */
    public static function fromArray(array $selections): self
    {
        $servingCounts = [];
        foreach ($selections as $recipeId => $servingCount) {
            if ( ! is_numeric($recipeId) || ! is_string($servingCount)) {
                continue;
            }

            $servingCounts[(int) $recipeId] = ServingCount::from($servingCount);
        }

        return new self($servingCounts);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function add(int $recipeId, ServingCount $servingCount): self
    {
        $selections = $this->selections;
        $selections[$recipeId] = isset($selections[$recipeId])
            ? $selections[$recipeId]->plus($servingCount)
            : $servingCount;

        return new self($selections);
    }

    public function servingCountFor(int $recipeId): ?ServingCount
    {
        return $this->selections[$recipeId] ?? null;
    }

    public function remove(int $recipeId): self
    {
        $selections = $this->selections;
        unset($selections[$recipeId]);

        return new self($selections);
    }

    /** @return array<int, string> */
    public function toArray(): array
    {
        return array_map(
            static fn (ServingCount $servingCount): string => $servingCount->toString(),
            $this->selections,
        );
    }

    /** @return list<int> */
    public function recipeIds(): array
    {
        return array_keys($this->selections);
    }

    public function isEmpty(): bool
    {
        return $this->selections === [];
    }
}
