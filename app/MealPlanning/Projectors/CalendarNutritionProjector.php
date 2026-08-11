<?php

declare(strict_types=1);

namespace App\MealPlanning\Projectors;

use App\Cookbook\Values\RecipeNutrition;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;

final class CalendarNutritionProjector
{
    /**
     * @return array{status: string, source: string, totals: array{energyKcal: string, fatGrams: string, proteinGrams: string, carbohydrateGrams: string}, missingIngredientNames: list<string>}
     */
    public function scale(RecipeNutrition $nutrition, string $servingCount): array
    {
        $perServing = $nutrition->perServing ?? $this->zeroTotals();

        return [
            'status' => $nutrition->status,
            'source' => $nutrition->status === 'override' ? 'override' : 'calculated',
            'totals' => $this->multiply($perServing, BigRational::of($servingCount)),
            'missingIngredientNames' => $nutrition->missingIngredientNames,
        ];
    }

    /**
     * @param  list<array{status: string, source: string, totals: array{energyKcal: string, fatGrams: string, proteinGrams: string, carbohydrateGrams: string}, missingIngredientNames: list<string>}>  $contributions
     * @return array{status: string, totals: array{energyKcal: string, fatGrams: string, proteinGrams: string, carbohydrateGrams: string}, missingIngredientNames: list<string>}
     */
    public function sum(array $contributions): array
    {
        $totals = array_map(BigRational::of(...), $this->zeroTotals());
        $missing = [];
        foreach ($contributions as $contribution) {
            foreach ($totals as $key => $total) {
                $totals[$key] = $total->plus($contribution['totals'][$key]);
            }
            array_push($missing, ...$contribution['missingIngredientNames']);
        }

        return [
            'status' => $missing === [] ? 'complete' : 'incomplete',
            'totals' => array_map($this->decimal(...), $totals),
            'missingIngredientNames' => array_values(array_unique($missing)),
        ];
    }

    /**
     * @param  array{energyKcal: string, fatGrams: string, proteinGrams: string, carbohydrateGrams: string}  $totals
     * @return array{energyKcal: string, fatGrams: string, proteinGrams: string, carbohydrateGrams: string}
     */
    private function multiply(array $totals, BigRational $factor): array
    {
        return array_map(
            fn (string $value): string => $this->decimal(BigRational::of($value)->multipliedBy($factor)),
            $totals,
        );
    }

    private function decimal(BigRational $value): string
    {
        return (string) $value->toScale(6, RoundingMode::HalfUp);
    }

    /** @return array{energyKcal: string, fatGrams: string, proteinGrams: string, carbohydrateGrams: string} */
    private function zeroTotals(): array
    {
        return ['energyKcal' => '0', 'fatGrams' => '0', 'proteinGrams' => '0', 'carbohydrateGrams' => '0'];
    }
}
