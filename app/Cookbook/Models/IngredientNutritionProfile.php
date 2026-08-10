<?php

declare(strict_types=1);

namespace App\Cookbook\Models;

use Database\Factories\IngredientNutritionProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $ingredient_id
 * @property 'package'|'grams'|'millilitres'|'piece' $basis_kind
 * @property string $basis_quantity
 * @property string $energy_kcal
 * @property string $fat_grams
 * @property string $protein_grams
 * @property string $carbohydrate_grams
 */
#[Fillable(['basis_kind', 'basis_quantity', 'energy_kcal', 'fat_grams', 'protein_grams', 'carbohydrate_grams'])]
final class IngredientNutritionProfile extends Model
{
    /** @use HasFactory<IngredientNutritionProfileFactory> */
    use HasFactory;

    public $incrementing = false;

    protected $primaryKey = 'ingredient_id';

    /** @return BelongsTo<Ingredient, $this> */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    protected static function newFactory(): IngredientNutritionProfileFactory
    {
        return IngredientNutritionProfileFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'basis_quantity' => 'decimal:6', 'energy_kcal' => 'decimal:6',
            'fat_grams' => 'decimal:6', 'protein_grams' => 'decimal:6',
            'carbohydrate_grams' => 'decimal:6',
        ];
    }
}
