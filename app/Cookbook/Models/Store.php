<?php

declare(strict_types=1);

namespace App\Cookbook\Models;

use App\Cookbook\Values\NormalizedName;
use App\FamilyAccess\Models\Family;
use Database\Factories\StoreFactory;
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
 * @property int $section_order_version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['family_id', 'name'])]
final class Store extends Model
{
    /** @use HasFactory<StoreFactory> */
    use HasFactory;

    /** @return BelongsTo<Family, $this> */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /** @return BelongsToMany<StoreSection, $this> */
    public function storeSections(): BelongsToMany
    {
        return $this->belongsToMany(StoreSection::class)
            ->withPivot('position')
            ->orderByPivot('position');
    }

    protected static function newFactory(): StoreFactory
    {
        return StoreFactory::new();
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
