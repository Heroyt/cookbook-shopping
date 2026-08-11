<?php

declare(strict_types=1);

namespace App\AgentIntegration\ChangeSets;

use App\AgentIntegration\Catalog\CatalogResourceType;
use App\AgentIntegration\Catalog\FamilyCatalog;
use App\AgentIntegration\Exceptions\AgentApiException;
use App\Cookbook\Actions\ArchiveIngredient;
use App\Cookbook\Actions\ArchiveRecipe;
use App\Cookbook\Actions\AttachIngredientAlternative;
use App\Cookbook\Actions\AttachStoreSection;
use App\Cookbook\Actions\CreateIngredient;
use App\Cookbook\Actions\CreateRecipe;
use App\Cookbook\Actions\CreateRecipeTag;
use App\Cookbook\Actions\CreateStore;
use App\Cookbook\Actions\CreateStoreSection;
use App\Cookbook\Actions\DeleteRecipeTag;
use App\Cookbook\Actions\DeleteStore;
use App\Cookbook\Actions\DeleteStoreSection;
use App\Cookbook\Actions\DetachIngredientAlternative;
use App\Cookbook\Actions\DetachStoreSection;
use App\Cookbook\Actions\RenameRecipeTag;
use App\Cookbook\Actions\RenameStore;
use App\Cookbook\Actions\ReorderStoreSections;
use App\Cookbook\Actions\RestoreIngredient;
use App\Cookbook\Actions\RestoreRecipe;
use App\Cookbook\Actions\UpdateIngredient;
use App\Cookbook\Actions\UpdateRecipe;
use App\Cookbook\Actions\UpdateStoreSection;
use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\Store;
use App\Cookbook\Values\IngredientNutritionInput;
use App\Cookbook\Values\IngredientPackageQuantities;
use App\Cookbook\Values\StoreSectionIcon;
use App\FamilyAccess\AuthorizedFamilyContext;
use App\MealPlanning\Actions\CreateCalendarEntry;
use App\MealPlanning\Actions\DeleteCalendarEntry;
use App\MealPlanning\Actions\UpdateCalendarEntry;
use App\MealPlanning\Values\MealLabel;
use App\MealPlanning\Values\ServingCount;
use LogicException;

final readonly class AgentOperationApplier
{
    public function __construct(
        private CreateStore $createStore,
        private CreateStoreSection $createStoreSection,
        private AttachStoreSection $attachStoreSection,
        private CreateIngredient $createIngredient,
        private AttachIngredientAlternative $attachIngredientAlternative,
        private CreateRecipeTag $createRecipeTag,
        private CreateRecipe $createRecipe,
        private CreateCalendarEntry $createCalendarEntry,
        private RenameStore $renameStore,
        private DetachStoreSection $detachStoreSection,
        private ReorderStoreSections $reorderStoreSections,
        private UpdateStoreSection $updateStoreSection,
        private UpdateIngredient $updateIngredient,
        private DetachIngredientAlternative $detachIngredientAlternative,
        private RenameRecipeTag $renameRecipeTag,
        private UpdateRecipe $updateRecipe,
        private UpdateCalendarEntry $updateCalendarEntry,
        private ArchiveIngredient $archiveIngredient,
        private RestoreIngredient $restoreIngredient,
        private ArchiveRecipe $archiveRecipe,
        private RestoreRecipe $restoreRecipe,
        private DeleteStore $deleteStore,
        private DeleteStoreSection $deleteStoreSection,
        private DeleteRecipeTag $deleteRecipeTag,
        private DeleteCalendarEntry $deleteCalendarEntry,
        private FamilyCatalog $catalog,
    ) {}

    /**
     * @param  array<string, mixed>  $canonicalRequest
     * @param  list<string>  $executionOrder
     * @return array{identifier_mappings: array<string, int>, result: array<string, mixed>}
     */
    public function apply(AuthorizedFamilyContext $context, array $canonicalRequest, array $executionOrder): array
    {
        $operations = $canonicalRequest['operations'] ?? null;
        if ( ! is_array($operations) || ! array_is_list($operations)) {
            throw new LogicException('A validated Change Set requires an operation list.');
        }

        $byId = $this->operationsById($operations);

        $mappings = [];
        $outcomes = [];
        $resources = [];
        foreach ($executionOrder as $operationId) {
            $operation = $byId[$operationId] ?? null;
            if ( ! is_array($operation)) {
                throw new LogicException('The preview execution order references an unavailable operation.');
            }
            $resourceType = $this->string($operation, 'resource_type', $operationId);
            $action = $this->string($operation, 'action', $operationId);
            if ($action === 'create') {
                $localRef = $this->string($operation, 'local_ref', $operationId);
                $resourceId = $this->create($context, $resourceType, $this->object($operation, 'data', $operationId), $mappings, $operationId);
                $mappings[$localRef] = $resourceId;
            } else {
                $resourceId = $this->positiveInt($operation['resource_id'] ?? null, $operationId);
                $this->mutate($context, $resourceType, $action, $resourceId, $operation, $mappings, $operationId);
            }
            if ($action !== 'delete') {
                $resources[] = $this->catalog->detail($context, CatalogResourceType::from($resourceType), $resourceId);
            }
            $outcomes[] = [
                'operation_id' => $operationId,
                'resource_type' => $resourceType,
                'action' => $action,
                'resource_id' => $resourceId,
                'status' => 'applied',
            ];
        }

        return [
            'identifier_mappings' => $mappings,
            'result' => [
                'version' => 1,
                'outcome' => 'applied',
                'operations' => $outcomes,
                'resources' => $resources,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $operation
     * @param  array<string, int>  $mappings
     */
    private function mutate(
        AuthorizedFamilyContext $context,
        string $resourceType,
        string $action,
        int $resourceId,
        array $operation,
        array $mappings,
        string $operationId,
    ): void {
        if ($action === 'update') {
            match ($resourceType) {
                'stores' => $this->updateStore($context, $resourceId, $operation, $mappings, $operationId),
                'store_sections' => $this->updateSection($context, $resourceId, $operation, $operationId),
                'ingredients' => $this->updateIngredient($context, $resourceId, $operation, $mappings, $operationId),
                'recipe_tags' => $this->renameRecipeTag->handle($context, $resourceId, $this->string($this->changes($operation, $operationId), 'name', $operationId)),
                'recipes' => $this->updateRecipe($context, $resourceId, $operation, $mappings, $operationId),
                'calendar_entries' => $this->updateCalendar($context, $resourceId, $operation, $mappings, $operationId),
                default => throw new LogicException('A validated resource type has no update handler.'),
            };

            return;
        }

        match ([$resourceType, $action]) {
            ['ingredients', 'archive'] => $this->archiveIngredient->handle($context, $resourceId),
            ['ingredients', 'restore'] => $this->restoreIngredient->handle($context, $resourceId),
            ['recipes', 'archive'] => $this->archiveRecipe->handle($context, $resourceId),
            ['recipes', 'restore'] => $this->restoreRecipe->handle($context, $resourceId),
            ['stores', 'delete'] => $this->deleteStore->handle($context, $resourceId),
            ['store_sections', 'delete'] => $this->deleteStoreSection->handle($context, $resourceId),
            ['recipe_tags', 'delete'] => $this->deleteRecipeTag->handle($context, $resourceId),
            ['calendar_entries', 'delete'] => $this->deleteCalendarEntry->handle($context, $resourceId),
            default => throw new LogicException('A validated resource action has no mutation handler.'),
        };
    }

    /**
     * @param  array<string, mixed>  $operation
     * @param  array<string, int>  $mappings
     */
    private function updateStore(AuthorizedFamilyContext $context, int $storeId, array $operation, array $mappings, string $operationId): void
    {
        $store = Store::query()->whereBelongsTo($context->family)->with('storeSections')->findOrFail($storeId);
        $changes = $this->changes($operation, $operationId);
        $this->renameStore->handle($context, $storeId, is_string($changes['name'] ?? null) ? $changes['name'] : $store->name);
        if (array_key_exists('store_section_ids', $changes)) {
            $desired = $this->referenceList($changes['store_section_ids'], $mappings, $operationId);
            $current = $this->intList($store->storeSections->pluck('id')->all(), $operationId);
            foreach (array_diff($current, $desired) as $sectionId) {
                $this->detachStoreSection->handle($context, $storeId, $sectionId);
            }
            foreach (array_diff($desired, $current) as $sectionId) {
                $this->attachStoreSection->handle($context, $storeId, $sectionId);
            }
            if ($desired !== []) {
                $fresh = Store::query()->whereBelongsTo($context->family)->findOrFail($storeId);
                $this->reorderStoreSections->handle($context, $storeId, $desired, $fresh->section_order_version);
            }
        }
    }

    /** @param array<string, mixed> $operation */
    private function updateSection(AuthorizedFamilyContext $context, int $sectionId, array $operation, string $operationId): void
    {
        $current = $this->catalog->detail($context, CatalogResourceType::StoreSections, $sectionId);
        $changes = $this->changes($operation, $operationId);
        $this->updateStoreSection->handle(
            $context,
            $sectionId,
            is_string($changes['name'] ?? null) ? $changes['name'] : $this->string($current, 'name', $operationId),
            is_string($changes['colour'] ?? null) ? $changes['colour'] : $this->string($current, 'colour', $operationId),
            StoreSectionIcon::from(is_string($changes['icon'] ?? null) ? $changes['icon'] : $this->string($current, 'icon', $operationId)),
        );
    }

    /**
     * @param  array<string, mixed>  $operation
     * @param  array<string, int>  $mappings
     */
    private function updateIngredient(AuthorizedFamilyContext $context, int $ingredientId, array $operation, array $mappings, string $operationId): void
    {
        $current = $this->catalog->detail($context, CatalogResourceType::Ingredients, $ingredientId);
        $changes = $this->changes($operation, $operationId);
        $quantities = is_array($changes['package_quantities'] ?? null)
            ? $this->stringObject($changes['package_quantities'])
            : $this->object($current, 'package_quantities', $operationId);
        $placement = array_key_exists('store_placement', $changes)
            ? (is_array($changes['store_placement'])
                ? $this->stringObject($changes['store_placement'])
                : ['store_id' => null, 'store_section_id' => null])
            : $this->object($current, 'store_placement', $operationId);
        $nutritionSource = array_key_exists('nutrition_profile', $changes) ? $changes['nutrition_profile'] : ($current['nutrition_profile'] ?? null);
        $nutrition = is_array($nutritionSource) ? $this->nutrition($this->stringObject($nutritionSource), $operationId) : null;
        $this->updateIngredient->handle(
            $context,
            $ingredientId,
            is_string($changes['name'] ?? null) ? $changes['name'] : $this->string($current, 'name', $operationId),
            array_key_exists('description', $changes) ? $this->nullableString($changes['description'], $operationId) : $this->nullableString($current['description'] ?? null, $operationId),
            new IngredientPackageQuantities(
                $this->nullableString($quantities['weight_grams'] ?? null, $operationId),
                $this->nullableString($quantities['volume_millilitres'] ?? null, $operationId),
                $this->nullableString($quantities['piece_count'] ?? null, $operationId),
            ),
            $this->nullableReferenceId($placement['store_id'] ?? null, $mappings, $operationId),
            $this->nullableReferenceId($placement['store_section_id'] ?? null, $mappings, $operationId),
            $nutrition,
        );
        if (array_key_exists('alternative_ingredient_ids', $changes)) {
            $desired = $this->referenceList($changes['alternative_ingredient_ids'], $mappings, $operationId);
            $currentIds = $this->intList($current['alternative_ingredient_ids'] ?? null, $operationId);
            foreach (array_diff($currentIds, $desired) as $alternativeId) {
                $this->detachIngredientAlternative->handle($context, $ingredientId, $alternativeId);
            }
            foreach (array_diff($desired, $currentIds) as $alternativeId) {
                $this->attachIngredientAlternative->handle($context, $ingredientId, $alternativeId);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $operation
     * @param  array<string, int>  $mappings
     */
    private function updateRecipe(AuthorizedFamilyContext $context, int $recipeId, array $operation, array $mappings, string $operationId): void
    {
        $recipe = Recipe::query()->whereBelongsTo($context->family)->findOrFail($recipeId);
        $current = $this->catalog->detail($context, CatalogResourceType::Recipes, $recipeId);
        $changes = $this->changes($operation, $operationId);
        $ingredientsSource = $changes['ingredients'] ?? $current['ingredients'] ?? null;
        $ingredients = [];
        foreach ($this->list($ingredientsSource, $operationId) as $line) {
            if ( ! is_array($line)) {
                throw $this->invalid($operationId);
            }
            $line = $this->stringObject($line);
            $ingredients[] = [
                'ingredient_id' => $this->referenceId($line['ingredient_id'] ?? null, $mappings, $operationId),
                'quantity' => $this->string($line, 'quantity', $operationId),
                'quantity_kind' => $this->string($line, 'quantity_kind', $operationId),
            ];
        }
        $steps = $this->stringList($changes['steps'] ?? $this->stepInstructions($current['steps'] ?? null, $operationId), $operationId);
        $tagIds = array_key_exists('recipe_tag_ids', $changes)
            ? $this->referenceList($changes['recipe_tag_ids'], $mappings, $operationId)
            : $this->intList($current['recipe_tag_ids'] ?? null, $operationId);
        $nutritionSource = array_key_exists('nutrition_override', $changes) ? $changes['nutrition_override'] : ($current['nutrition_override'] ?? null);
        $nutrition = is_array($nutritionSource) ? $this->recipeNutrition($this->stringObject($nutritionSource), $operationId) : null;
        $this->updateRecipe->handle($context, $recipeId, $recipe->version, [
            'name' => is_string($changes['name'] ?? null) ? $changes['name'] : $this->string($current, 'name', $operationId),
            'base_servings' => is_string($changes['base_servings'] ?? null) ? $changes['base_servings'] : $this->string($current, 'base_servings', $operationId),
            'source_url' => array_key_exists('source_url', $changes) ? $this->nullableString($changes['source_url'], $operationId) : $this->nullableString($current['source_url'] ?? null, $operationId),
            'preparation_minutes' => array_key_exists('preparation_minutes', $changes) ? $this->nullableInt($changes['preparation_minutes'], $operationId) : $this->nullableInt($current['preparation_minutes'] ?? null, $operationId),
            'cooking_minutes' => array_key_exists('cooking_minutes', $changes) ? $this->nullableInt($changes['cooking_minutes'], $operationId) : $this->nullableInt($current['cooking_minutes'] ?? null, $operationId),
            'notes' => array_key_exists('notes', $changes) ? $this->nullableString($changes['notes'], $operationId) : $this->nullableString($current['notes'] ?? null, $operationId),
            'ingredients' => $ingredients,
            'steps' => $steps,
            'tag_ids' => $tagIds,
            'nutrition' => $nutrition,
        ]);
    }

    /**
     * @param  array<string, mixed>  $operation
     * @param  array<string, int>  $mappings
     */
    private function updateCalendar(AuthorizedFamilyContext $context, int $entryId, array $operation, array $mappings, string $operationId): void
    {
        $current = $this->catalog->detail($context, CatalogResourceType::CalendarEntries, $entryId);
        $changes = $this->changes($operation, $operationId);
        $label = $changes['meal_label'] ?? $current['meal_label'] ?? null;
        $this->updateCalendarEntry->handle(
            $context,
            $entryId,
            array_key_exists('recipe_id', $changes) ? $this->referenceId($changes['recipe_id'], $mappings, $operationId) : $this->positiveInt($current['recipe_id'] ?? null, $operationId),
            is_string($changes['date'] ?? null) ? $changes['date'] : $this->string($current, 'date', $operationId),
            is_string($label) ? MealLabel::from($label) : null,
            ServingCount::from(is_string($changes['serving_count'] ?? null) ? $changes['serving_count'] : $this->string($current, 'serving_count', $operationId)),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, int>  $mappings
     */
    private function create(AuthorizedFamilyContext $context, string $resourceType, array $data, array $mappings, string $operationId): int
    {
        return match ($resourceType) {
            'stores' => $this->createStore($context, $data, $mappings, $operationId),
            'store_sections' => $this->createStoreSection($context, $data, $operationId),
            'ingredients' => $this->createIngredient($context, $data, $mappings, $operationId),
            'recipe_tags' => $this->createRecipeTag->handle($context, $this->string($data, 'name', $operationId))->id,
            'recipes' => $this->createRecipe($context, $data, $mappings, $operationId),
            'calendar_entries' => $this->createCalendarEntry($context, $data, $mappings, $operationId),
            default => throw new LogicException('A validated resource type has no create handler.'),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, int>  $mappings
     */
    private function createStore(AuthorizedFamilyContext $context, array $data, array $mappings, string $operationId): int
    {
        $store = $this->createStore->handle($context, $this->string($data, 'name', $operationId));
        foreach ($this->list($data['store_section_ids'] ?? [], $operationId) as $reference) {
            $this->attachStoreSection->handle($context, $store->id, $this->referenceId($reference, $mappings, $operationId));
        }

        return $store->id;
    }

    /** @param array<string, mixed> $data */
    private function createStoreSection(AuthorizedFamilyContext $context, array $data, string $operationId): int
    {
        return $this->createStoreSection->handle(
            $context,
            $this->string($data, 'name', $operationId),
            $this->string($data, 'colour', $operationId),
            StoreSectionIcon::from($this->string($data, 'icon', $operationId)),
        )->id;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, int>  $mappings
     */
    private function createIngredient(AuthorizedFamilyContext $context, array $data, array $mappings, string $operationId): int
    {
        $quantities = $this->object($data, 'package_quantities', $operationId);
        $placement = $this->object($data, 'store_placement', $operationId);
        $nutritionData = $data['nutrition_profile'] ?? null;
        $nutrition = null;
        if (is_array($nutritionData)) {
            $nutritionData = $this->stringObject($nutritionData);
            $nutrition = new IngredientNutritionInput(
                $this->string($nutritionData, 'basis_kind', $operationId),
                $this->string($nutritionData, 'basis_quantity', $operationId),
                $this->string($nutritionData, 'energy_kcal', $operationId),
                $this->string($nutritionData, 'fat_grams', $operationId),
                $this->string($nutritionData, 'protein_grams', $operationId),
                $this->string($nutritionData, 'carbohydrate_grams', $operationId),
            );
        }

        $ingredient = $this->createIngredient->handle(
            $context,
            $this->string($data, 'name', $operationId),
            $this->nullableString($data['description'] ?? null, $operationId),
            new IngredientPackageQuantities(
                $this->nullableString($quantities['weight_grams'] ?? null, $operationId),
                $this->nullableString($quantities['volume_millilitres'] ?? null, $operationId),
                $this->nullableString($quantities['piece_count'] ?? null, $operationId),
            ),
            $this->nullableReferenceId($placement['store_id'] ?? null, $mappings, $operationId),
            $this->nullableReferenceId($placement['store_section_id'] ?? null, $mappings, $operationId),
            $nutrition,
        );
        foreach ($this->list($data['alternative_ingredient_ids'] ?? [], $operationId) as $reference) {
            $this->attachIngredientAlternative->handle($context, $ingredient->id, $this->referenceId($reference, $mappings, $operationId));
        }

        return $ingredient->id;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, int>  $mappings
     */
    private function createRecipe(AuthorizedFamilyContext $context, array $data, array $mappings, string $operationId): int
    {
        $ingredients = [];
        foreach ($this->list($data['ingredients'] ?? null, $operationId) as $line) {
            if ( ! is_array($line)) {
                throw $this->invalid($operationId);
            }
            $line = $this->stringObject($line);
            $ingredients[] = [
                'ingredient_id' => $this->referenceId($line['ingredient_id'] ?? null, $mappings, $operationId),
                'quantity' => $this->string($line, 'quantity', $operationId),
                'quantity_kind' => $this->string($line, 'quantity_kind', $operationId),
            ];
        }
        $steps = [];
        foreach ($this->list($data['steps'] ?? null, $operationId) as $step) {
            if ( ! is_string($step)) {
                throw $this->invalid($operationId);
            }
            $steps[] = $step;
        }
        $tagIds = [];
        foreach ($this->list($data['recipe_tag_ids'] ?? null, $operationId) as $reference) {
            $tagIds[] = $this->referenceId($reference, $mappings, $operationId);
        }
        $nutrition = null;
        if (is_array($data['nutrition_override'] ?? null)) {
            $source = $this->stringObject($data['nutrition_override']);
            $nutrition = [
                'energy_kcal' => $this->string($source, 'energy_kcal', $operationId),
                'fat_grams' => $this->string($source, 'fat_grams', $operationId),
                'protein_grams' => $this->string($source, 'protein_grams', $operationId),
                'carbohydrate_grams' => $this->string($source, 'carbohydrate_grams', $operationId),
            ];
        }

        return $this->createRecipe->handle($context, [
            'name' => $this->string($data, 'name', $operationId),
            'base_servings' => $this->string($data, 'base_servings', $operationId),
            'source_url' => $this->nullableString($data['source_url'] ?? null, $operationId),
            'preparation_minutes' => $this->nullableInt($data['preparation_minutes'] ?? null, $operationId),
            'cooking_minutes' => $this->nullableInt($data['cooking_minutes'] ?? null, $operationId),
            'notes' => $this->nullableString($data['notes'] ?? null, $operationId),
            'ingredients' => $ingredients,
            'steps' => $steps,
            'tag_ids' => $tagIds,
            'nutrition' => $nutrition,
        ])->id;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, int>  $mappings
     */
    private function createCalendarEntry(AuthorizedFamilyContext $context, array $data, array $mappings, string $operationId): int
    {
        $label = $data['meal_label'] ?? null;
        if ($label !== null && ! is_string($label)) {
            throw $this->invalid($operationId);
        }

        return $this->createCalendarEntry->handle(
            $context,
            $this->referenceId($data['recipe_id'] ?? null, $mappings, $operationId),
            $this->string($data, 'date', $operationId),
            is_string($label) ? MealLabel::from($label) : null,
            ServingCount::from($this->string($data, 'serving_count', $operationId)),
        )->entry->id;
    }

    /** @param array<string, int> $mappings */
    private function nullableReferenceId(mixed $reference, array $mappings, string $operationId): ?int
    {
        return $reference === null ? null : $this->referenceId($reference, $mappings, $operationId);
    }

    /** @param array<string, int> $mappings */
    private function referenceId(mixed $reference, array $mappings, string $operationId): int
    {
        if (is_int($reference) && $reference > 0) {
            return $reference;
        }
        if (is_array($reference) && is_string($reference['local_ref'] ?? null) && isset($mappings[$reference['local_ref']])) {
            return $mappings[$reference['local_ref']];
        }

        throw $this->invalid($operationId);
    }

    /**
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    private function object(array $source, string $key, string $operationId): array
    {
        $value = $source[$key] ?? null;
        if ( ! is_array($value) || array_is_list($value)) {
            throw $this->invalid($operationId);
        }

        return $this->stringObject($value);
    }

    /** @return list<mixed> */
    private function list(mixed $value, string $operationId): array
    {
        if ( ! is_array($value) || ! array_is_list($value)) {
            throw $this->invalid($operationId);
        }

        return $value;
    }

    /** @param array<string, mixed> $source */
    private function string(array $source, string $key, string $operationId): string
    {
        $value = $source[$key] ?? null;
        if ( ! is_string($value)) {
            throw $this->invalid($operationId);
        }

        return $value;
    }

    private function nullableString(mixed $value, string $operationId): ?string
    {
        if ($value !== null && ! is_string($value)) {
            throw $this->invalid($operationId);
        }

        return $value;
    }

    private function nullableInt(mixed $value, string $operationId): ?int
    {
        if ($value !== null && ! is_int($value)) {
            throw $this->invalid($operationId);
        }

        return $value;
    }

    /**
     * @param  array<mixed, mixed>  $value
     * @return array<string, mixed>
     */
    private function stringObject(array $value): array
    {
        $object = [];
        foreach ($value as $key => $child) {
            if ( ! is_string($key)) {
                throw new LogicException('A canonical JSON object must use string keys.');
            }
            $object[$key] = $child;
        }

        return $object;
    }

    /**
     * @param  list<mixed>  $operations
     * @return array<string, array<string, mixed>>
     */
    private function operationsById(array $operations): array
    {
        $byId = [];
        foreach ($operations as $operation) {
            if ( ! is_array($operation) || ! is_string($operation['operation_id'] ?? null)) {
                throw new LogicException('A validated Change Set operation requires an identifier.');
            }
            $byId[$operation['operation_id']] = $this->stringObject($operation);
        }

        return $byId;
    }

    /**
     * @param  array<string, mixed>  $operation
     * @return array<string, mixed>
     */
    private function changes(array $operation, string $operationId): array
    {
        $set = $operation['set'] ?? [];
        if ( ! is_array($set) || ($set !== [] && array_is_list($set))) {
            throw $this->invalid($operationId);
        }
        $changes = $this->stringObject($set);
        foreach ($this->list($operation['unset'] ?? [], $operationId) as $field) {
            if ( ! is_string($field)) {
                throw $this->invalid($operationId);
            }
            $changes[$field] = null;
        }

        return $changes;
    }

    /**
     * @param  array<string, int>  $mappings
     * @return list<int>
     */
    private function referenceList(mixed $value, array $mappings, string $operationId): array
    {
        $ids = [];
        foreach ($this->list($value, $operationId) as $reference) {
            $ids[] = $this->referenceId($reference, $mappings, $operationId);
        }
        if (count(array_unique($ids)) !== count($ids)) {
            throw $this->invalid($operationId);
        }

        return $ids;
    }

    /** @return list<int> */
    private function intList(mixed $value, string $operationId): array
    {
        $ids = [];
        foreach ($this->list($value, $operationId) as $id) {
            $ids[] = $this->positiveInt($id, $operationId);
        }

        return $ids;
    }

    /** @return list<string> */
    private function stringList(mixed $value, string $operationId): array
    {
        $strings = [];
        foreach ($this->list($value, $operationId) as $item) {
            if ( ! is_string($item)) {
                throw $this->invalid($operationId);
            }
            $strings[] = $item;
        }

        return $strings;
    }

    /** @return list<string> */
    private function stepInstructions(mixed $value, string $operationId): array
    {
        $steps = [];
        foreach ($this->list($value, $operationId) as $step) {
            if ( ! is_array($step)) {
                throw $this->invalid($operationId);
            }
            $steps[] = $this->string($this->stringObject($step), 'instruction', $operationId);
        }

        return $steps;
    }

    /** @param array<string, mixed> $source */
    private function nutrition(array $source, string $operationId): IngredientNutritionInput
    {
        return new IngredientNutritionInput(
            $this->string($source, 'basis_kind', $operationId),
            $this->string($source, 'basis_quantity', $operationId),
            $this->string($source, 'energy_kcal', $operationId),
            $this->string($source, 'fat_grams', $operationId),
            $this->string($source, 'protein_grams', $operationId),
            $this->string($source, 'carbohydrate_grams', $operationId),
        );
    }

    /**
     * @param  array<string, mixed>  $source
     * @return array{energy_kcal: string, fat_grams: string, protein_grams: string, carbohydrate_grams: string}
     */
    private function recipeNutrition(array $source, string $operationId): array
    {
        return [
            'energy_kcal' => $this->string($source, 'energy_kcal', $operationId),
            'fat_grams' => $this->string($source, 'fat_grams', $operationId),
            'protein_grams' => $this->string($source, 'protein_grams', $operationId),
            'carbohydrate_grams' => $this->string($source, 'carbohydrate_grams', $operationId),
        ];
    }

    private function positiveInt(mixed $value, string $operationId): int
    {
        if ( ! is_int($value) || $value < 1) {
            throw $this->invalid($operationId);
        }

        return $value;
    }

    private function invalid(string $operationId): AgentApiException
    {
        return new AgentApiException('validation_failed', 'The canonical operation document is invalid.', 422, operationId: $operationId);
    }
}
