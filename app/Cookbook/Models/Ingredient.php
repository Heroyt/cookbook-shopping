<?php

declare(strict_types=1);

namespace App\Cookbook\Models;

use App\Cookbook\Values\IngredientPackageQuantities;
use App\Cookbook\Values\NormalizedName;
use App\FamilyAccess\Models\Family;
use Database\Factories\IngredientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $family_id
 * @property string $name
 * @property string $normalized_name
 * @property string|null $weight_grams
 * @property string|null $volume_millilitres
 * @property string|null $piece_count
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['family_id', 'name', 'weight_grams', 'volume_millilitres', 'piece_count', 'description'])]
final class Ingredient extends Model
{
    /** @use HasFactory<IngredientFactory> */
    use HasFactory;

    /** @return BelongsTo<Family, $this> */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function packageQuantities(): IngredientPackageQuantities
    {
        return new IngredientPackageQuantities(
            $this->weight_grams,
            $this->volume_millilitres,
            $this->piece_count,
        );
    }

    protected static function newFactory(): IngredientFactory
    {
        return IngredientFactory::new();
    }

    /** @return Attribute<string, string> */
    protected function name(): Attribute
    {
        return Attribute::set(function (string $value): array {
            $name = NormalizedName::from($value);

            return [
                'name' => $name->display,
                'normalized_name' => $name->key,
            ];
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'weight_grams' => 'decimal:6',
            'volume_millilitres' => 'decimal:6',
            'piece_count' => 'decimal:6',
        ];
    }
}
