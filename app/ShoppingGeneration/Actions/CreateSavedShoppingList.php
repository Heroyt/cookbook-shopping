<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Actions;

use App\FamilyAccess\Models\Family;
use App\ShoppingGeneration\Models\SavedShoppingList;
use App\ShoppingGeneration\Snapshots\SavedShoppingListPayload;
use App\ShoppingGeneration\Values\SavedShoppingListSource;
use Carbon\CarbonImmutable;

/** @phpstan-import-type PayloadV1 from \App\ShoppingGeneration\Snapshots\SavedShoppingListV1 */
final readonly class CreateSavedShoppingList
{
    /** @param PayloadV1 $payload */
    public function handle(Family $family, SavedShoppingListSource $source, array $payload): SavedShoppingList
    {
        return SavedShoppingList::query()->create([
            'family_id' => $family->id,
            'generated_at' => CarbonImmutable::now(),
            'source_kind' => $source,
            'payload_schema_version' => SavedShoppingListPayload::SCHEMA_VERSION,
            'payload' => $payload,
        ]);
    }
}
