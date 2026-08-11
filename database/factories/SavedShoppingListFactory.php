<?php

declare(strict_types=1);

namespace Database\Factories;

use App\FamilyAccess\Models\Family;
use App\ShoppingGeneration\Models\SavedShoppingList;
use App\ShoppingGeneration\Snapshots\SavedShoppingListPayload;
use App\ShoppingGeneration\Values\SavedShoppingListSource;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @phpstan-import-type PayloadV1 from \App\ShoppingGeneration\Snapshots\SavedShoppingListV1
 *
 * @extends Factory<SavedShoppingList>
 */
final class SavedShoppingListFactory extends Factory
{
    protected $model = SavedShoppingList::class;

    /** @return array{family_id: Factory<Family>, generated_at: CarbonImmutable, source_kind: SavedShoppingListSource, payload_schema_version: int, payload: PayloadV1} */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'generated_at' => now(),
            'source_kind' => SavedShoppingListSource::SimplePlan,
            'payload_schema_version' => SavedShoppingListPayload::SCHEMA_VERSION,
            'payload' => [
                'locale' => 'cs',
                'source' => ['kind' => 'simple_plan', 'recipes' => []],
                'appliedAlternatives' => [],
                'shoppingList' => ['storeGroups' => [], 'unplacedLines' => []],
            ],
        ];
    }
}
