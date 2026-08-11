<?php

declare(strict_types=1);

namespace App\Cookbook\Models;

use Database\Factories\RecipeStepFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['family_id', 'recipe_id', 'position', 'instruction'])]
final class RecipeStep extends Model
{
    /** @use HasFactory<RecipeStepFactory> */
    use HasFactory;

    /** @return BelongsTo<Recipe, $this> */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    protected static function newFactory(): RecipeStepFactory
    {
        return RecipeStepFactory::new();
    }
}
