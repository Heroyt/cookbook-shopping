<?php

declare(strict_types=1);

namespace App\Cookbook\Values;

final readonly class RecipeNutrition
{
    /**
     * @param  array{energyKcal: string, fatGrams: string, proteinGrams: string, carbohydrateGrams: string}|null  $perServing
     * @param  list<string>  $missingIngredientNames
     */
    public function __construct(public string $status, public ?array $perServing, public array $missingIngredientNames) {}
}
