<?php

declare(strict_types=1);

namespace App\MealPlanning\Models;

use App\Cookbook\Models\Recipe;
use App\FamilyAccess\Models\Family;
use Carbon\CarbonImmutable;
use Database\Factories\CalendarEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $family_id
 * @property int $recipe_id
 * @property CarbonImmutable $date
 * @property string $meal_label_key
 * @property string $serving_count
 */
#[Fillable(['family_id', 'recipe_id', 'date', 'meal_label_key', 'serving_count'])]
final class CalendarEntry extends Model
{
    /** @use HasFactory<CalendarEntryFactory> */
    use HasFactory;

    /** @return BelongsTo<Family, $this> */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /** @return BelongsTo<Recipe, $this> */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    protected static function newFactory(): CalendarEntryFactory
    {
        return CalendarEntryFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['date' => 'immutable_date:Y-m-d', 'serving_count' => 'decimal:6'];
    }
}
