<?php

declare(strict_types=1);

namespace App\Cookbook\Services;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\IngredientNutritionProfile;
use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\RecipeIngredient;
use App\Cookbook\Values\RecipeNutrition;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Collection;

final class RecipeNutritionCalculator
{
    public function calculate(Recipe $recipe): RecipeNutrition
    {
        $override = $this->override($recipe);
        if ($override !== null) {
            return new RecipeNutrition('override', $override, []);
        }

        $totals = [
            'energyKcal' => BigRational::zero(), 'fatGrams' => BigRational::zero(),
            'proteinGrams' => BigRational::zero(), 'carbohydrateGrams' => BigRational::zero(),
        ];
        $missing = [];
        $lines = $recipe->getRelation('ingredients');
        if ( ! $lines instanceof Collection) {
            $lines = $recipe->ingredients()->with('ingredient.nutritionProfile')->get();
        }
        foreach ($lines as $line) {
            if ( ! $line instanceof RecipeIngredient) {
                continue;
            }
            $ingredient = $line->relationLoaded('ingredient') ? $line->getRelation('ingredient') : $line->ingredient()->with('nutritionProfile')->first();
            if ( ! $ingredient instanceof Ingredient) {
                continue;
            }
            $profile = $ingredient->relationLoaded('nutritionProfile') ? $ingredient->getRelation('nutritionProfile') : $ingredient->nutritionProfile()->first();
            if ( ! $profile instanceof IngredientNutritionProfile) {
                $missing[] = $ingredient->name;

                continue;
            }
            $factor = $this->lineFactor($line, $ingredient, $profile);
            if ($factor === null) {
                $missing[] = $ingredient->name;

                continue;
            }
            $totals['energyKcal'] = $totals['energyKcal']->plus(BigRational::of($profile->energy_kcal)->multipliedBy($factor));
            $totals['fatGrams'] = $totals['fatGrams']->plus(BigRational::of($profile->fat_grams)->multipliedBy($factor));
            $totals['proteinGrams'] = $totals['proteinGrams']->plus(BigRational::of($profile->protein_grams)->multipliedBy($factor));
            $totals['carbohydrateGrams'] = $totals['carbohydrateGrams']->plus(BigRational::of($profile->carbohydrate_grams)->multipliedBy($factor));
        }
        if ($missing !== []) {
            return new RecipeNutrition('incomplete', null, array_values(array_unique($missing)));
        }
        $servings = BigRational::of($recipe->base_servings);

        return new RecipeNutrition('calculated', [
            'energyKcal' => (string) $totals['energyKcal']->dividedBy($servings)->toScale(6, RoundingMode::HalfUp),
            'fatGrams' => (string) $totals['fatGrams']->dividedBy($servings)->toScale(6, RoundingMode::HalfUp),
            'proteinGrams' => (string) $totals['proteinGrams']->dividedBy($servings)->toScale(6, RoundingMode::HalfUp),
            'carbohydrateGrams' => (string) $totals['carbohydrateGrams']->dividedBy($servings)->toScale(6, RoundingMode::HalfUp),
        ], []);
    }

    private function lineFactor(RecipeIngredient $line, Ingredient $ingredient, IngredientNutritionProfile $profile): ?BigRational
    {
        $linePackageQuantity = $this->packageQuantity($ingredient, $line->quantity_kind);
        if ($linePackageQuantity === null) {
            return null;
        }
        $linePackageFraction = BigRational::of($line->quantity)->dividedBy($linePackageQuantity);
        if ($profile->basis_kind === 'package') {
            return $linePackageFraction;
        }
        $basisPackageQuantity = $this->packageQuantity($ingredient, $profile->basis_kind);
        if ($basisPackageQuantity === null) {
            return null;
        }
        $basisPackageFraction = BigRational::of($profile->basis_quantity)->dividedBy($basisPackageQuantity);

        return $linePackageFraction->dividedBy($basisPackageFraction);
    }

    private function packageQuantity(Ingredient $ingredient, string $kind): ?string
    {
        return match ($kind) {
            'grams' => $ingredient->weight_grams,
            'millilitres' => $ingredient->volume_millilitres,
            'piece' => $ingredient->piece_count,
            default => null,
        };
    }

    /** @return array{energyKcal: string, fatGrams: string, proteinGrams: string, carbohydrateGrams: string}|null */
    private function override(Recipe $recipe): ?array
    {
        if ($recipe->nutrition_energy_kcal === null || $recipe->nutrition_fat_grams === null || $recipe->nutrition_protein_grams === null || $recipe->nutrition_carbohydrate_grams === null) {
            return null;
        }

        return [
            'energyKcal' => $recipe->nutrition_energy_kcal, 'fatGrams' => $recipe->nutrition_fat_grams,
            'proteinGrams' => $recipe->nutrition_protein_grams, 'carbohydrateGrams' => $recipe->nutrition_carbohydrate_grams,
        ];
    }
}
