<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Models;

use App\FamilyAccess\Models\Family;
use App\ShoppingGeneration\Values\SavedShoppingListSource;
use Carbon\CarbonImmutable;
use Database\Factories\SavedShoppingListFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * @phpstan-import-type PayloadV1 from \App\ShoppingGeneration\Snapshots\SavedShoppingListV1
 *
 * @property int $id
 * @property int $family_id
 * @property CarbonImmutable $generated_at
 * @property SavedShoppingListSource $source_kind
 * @property int $payload_schema_version
 * @property PayloadV1 $payload
 */
#[Fillable(['family_id', 'generated_at', 'source_kind', 'payload_schema_version', 'payload'])]
final class SavedShoppingList extends Model
{
    /** @use HasFactory<SavedShoppingListFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /** @return BelongsTo<Family, $this> */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    protected static function newFactory(): SavedShoppingListFactory
    {
        return SavedShoppingListFactory::new();
    }

    protected static function booted(): void
    {
        self::updating(static function (): never {
            throw new LogicException('Saved Shopping Lists are immutable.');
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'generated_at' => 'immutable_datetime',
            'source_kind' => SavedShoppingListSource::class,
            'payload_schema_version' => 'integer',
            'payload' => 'array',
        ];
    }
}
