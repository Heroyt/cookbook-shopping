<?php

declare(strict_types=1);

namespace App\Cookbook\Models;

use App\Cookbook\Values\NormalizedName;
use App\FamilyAccess\Models\Family;
use Database\Factories\StoreSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $family_id
 * @property string $name
 * @property string $normalized_name
 * @property string $colour
 * @property-read int $stores_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['family_id', 'name', 'colour'])]
final class StoreSection extends Model
{
    /** @use HasFactory<StoreSectionFactory> */
    use HasFactory;

    /** @return BelongsTo<Family, $this> */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /** @return BelongsToMany<Store, $this> */
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class)
            ->withPivot('position');
    }

    protected static function newFactory(): StoreSectionFactory
    {
        return StoreSectionFactory::new();
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
}
