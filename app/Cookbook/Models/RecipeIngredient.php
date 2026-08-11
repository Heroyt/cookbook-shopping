<?php

declare(strict_types=1);

namespace App\Cookbook\Models;

use Database\Factories\RecipeIngredientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $family_id
 * @property int $recipe_id
 * @property int $ingredient_id
 * @property int $position
 * @property string $quantity
 * @property string $quantity_kind
 */
#[Fillable(['family_id', 'recipe_id', 'ingredient_id', 'position', 'quantity', 'quantity_kind'])]
final class RecipeIngredient extends Model
{
    /** @use HasFactory<RecipeIngredientFactory> */
    use HasFactory;

    /** @return BelongsTo<Recipe, $this> */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /** @return BelongsTo<Ingredient, $this> */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    protected static function newFactory(): RecipeIngredientFactory
    {
        return RecipeIngredientFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['quantity' => 'decimal:6'];
    }
}
