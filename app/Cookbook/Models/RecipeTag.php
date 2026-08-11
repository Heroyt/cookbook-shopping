<?php

declare(strict_types=1);

namespace App\Cookbook\Models;

use App\Cookbook\Values\NormalizedName;
use App\FamilyAccess\Models\Family;
use Database\Factories\RecipeTagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['family_id', 'name'])]
final class RecipeTag extends Model
{
    /** @use HasFactory<RecipeTagFactory> */
    use HasFactory;

    /** @return BelongsTo<Family, $this> */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /** @return BelongsToMany<Recipe, $this> */
    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'recipe_recipe_tag')->withPivot('family_id')->withTimestamps();
    }

    protected static function newFactory(): RecipeTagFactory
    {
        return RecipeTagFactory::new();
    }

    /** @return Attribute<string, string> */
    protected function name(): Attribute
    {
        return Attribute::set(function (string $value): array {
            $name = NormalizedName::from($value);

            return ['name' => $name->display, 'normalized_name' => $name->key];
        });
    }
}
