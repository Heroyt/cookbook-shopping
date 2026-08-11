<?php

declare(strict_types=1);

namespace App\Cookbook\Models;

use App\Cookbook\Values\NormalizedName;
use App\FamilyAccess\Models\Family;
use Database\Factories\RecipeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $family_id
 * @property string $name
 * @property string $normalized_name
 * @property string $base_servings
 * @property int $version
 * @property string|null $source_url
 * @property int|null $preparation_minutes
 * @property int|null $cooking_minutes
 * @property string|null $notes
 * @property string|null $nutrition_energy_kcal
 * @property string|null $nutrition_fat_grams
 * @property string|null $nutrition_protein_grams
 * @property string|null $nutrition_carbohydrate_grams
 * @property Carbon|null $archived_at
 */
#[Fillable([
    'family_id', 'name', 'base_servings', 'version', 'source_url', 'preparation_minutes', 'cooking_minutes', 'notes',
    'nutrition_energy_kcal', 'nutrition_fat_grams', 'nutrition_protein_grams', 'nutrition_carbohydrate_grams', 'archived_at',
])]
final class Recipe extends Model
{
    /** @use HasFactory<RecipeFactory> */
    use HasFactory;

    /** @return BelongsTo<Family, $this> */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /** @return HasMany<RecipeIngredient, $this> */
    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class)->orderBy('position');
    }

    /** @return HasMany<RecipeStep, $this> */
    public function steps(): HasMany
    {
        return $this->hasMany(RecipeStep::class)->orderBy('position');
    }

    /** @return BelongsToMany<RecipeTag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(RecipeTag::class, 'recipe_recipe_tag')->withPivot('family_id')->withTimestamps();
    }

    protected static function newFactory(): RecipeFactory
    {
        return RecipeFactory::new();
    }

    /** @return Attribute<string, string> */
    protected function name(): Attribute
    {
        return Attribute::set(function (string $value): array {
            $name = NormalizedName::from($value);

            return ['name' => $name->display, 'normalized_name' => $name->key];
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'base_servings' => 'decimal:6',
            'nutrition_energy_kcal' => 'decimal:6',
            'nutrition_fat_grams' => 'decimal:6',
            'nutrition_protein_grams' => 'decimal:6',
            'nutrition_carbohydrate_grams' => 'decimal:6',
            'archived_at' => 'datetime',
        ];
    }
}
