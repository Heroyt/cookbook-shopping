<?php

declare(strict_types=1);

namespace App\AgentIntegration\ChangeSets;

use App\AgentIntegration\AgentCredentialAbility;
use App\AgentIntegration\Catalog\CatalogResourceType;
use App\AgentIntegration\Exceptions\AgentApiException;
use App\AgentIntegration\Models\AgentCredential;
use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\RecipeTag;
use App\Cookbook\Models\Store;
use App\Cookbook\Models\StoreSection;
use App\Cookbook\Values\NormalizedName;
use App\Cookbook\Values\StoreSectionIcon;
use App\FamilyAccess\AuthorizedFamilyContext;
use App\MealPlanning\Models\CalendarEntry;
use App\MealPlanning\Values\MealLabel;
use App\MealPlanning\Values\ServingCount;
use Brick\Math\BigDecimal;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Throwable;

final readonly class AgentOperationPreviewer
{
    /** @var list<string> */
    private const SUPPORTED_ACTIONS = ['create', 'update', 'archive', 'restore', 'delete'];

    /**
     * @param  array<string, mixed>  $canonicalRequest
     * @return array{version: int, effects: list<array<string, mixed>>, warnings: list<string>, execution_order: list<string>}
     */
    public function preview(
        AuthorizedFamilyContext $context,
        AgentCredential $credential,
        array $canonicalRequest,
    ): array {
        $operations = $canonicalRequest['operations'] ?? null;

        if ( ! is_array($operations) || ! array_is_list($operations) || $operations === []) {
            throw new AgentApiException('validation_failed', 'The Change Set must contain at least one operation.', 422, '/operations');
        }

        $operationIds = [];
        $localRefs = [];
        foreach ($operations as $index => $operation) {
            if ( ! is_array($operation)) {
                throw new AgentApiException('validation_failed', 'Each operation must be a JSON object.', 422, "/operations/{$index}");
            }

            $operationId = $this->requiredString($operation, 'operation_id', "/operations/{$index}/operation_id");
            if (isset($operationIds[$operationId])) {
                throw new AgentApiException('validation_failed', 'Every operation_id must be unique within a Change Set.', 422, "/operations/{$index}/operation_id", $operationId);
            }
            $operationIds[$operationId] = $index;

            if (($operation['action'] ?? null) === 'create') {
                $localRef = $this->requiredString($operation, 'local_ref', "/operations/{$index}/local_ref", $operationId);
                if (isset($localRefs[$localRef])) {
                    throw new AgentApiException('validation_failed', 'Every local_ref must be unique within a Change Set.', 422, "/operations/{$index}/local_ref", $operationId);
                }
                $localRefs[$localRef] = [
                    'operation_id' => $operationId,
                    'resource_type' => $operation['resource_type'] ?? null,
                ];
            }
        }

        $effectsByOperation = [];
        $dependencies = [];
        $warnings = [];
        $pendingNames = [];

        foreach ($operations as $index => $operation) {
            /** @var array<string, mixed> $operation */
            $operationId = $this->requiredString($operation, 'operation_id', "/operations/{$index}/operation_id");
            $resourceType = $this->requiredString($operation, 'resource_type', "/operations/{$index}/resource_type", $operationId);
            $action = $this->requiredString($operation, 'action', "/operations/{$index}/action", $operationId);
            $this->validateSupportedAction($resourceType, $action, $index, $operationId);
            $this->authorize($credential, $resourceType, $action, $operationId);

            $operationDependencies = $this->localDependencies($operation, $localRefs, $index, $operationId);
            $this->validateOperation($context, $operation, $resourceType, $action, $index, $operationId, $pendingNames);
            $this->validateRelationshipScope($context, $operation, $resourceType, $action, $localRefs, $index, $operationId);
            $warningCodes = $this->warningCodes($resourceType, $action);
            array_push($warnings, ...$warningCodes);

            $target = $action === 'create'
                ? ['local_ref' => $operation['local_ref']]
                : ['resource_id' => $operation['resource_id'] ?? null];
            $effectsByOperation[$operationId] = [
                'operation_id' => $operationId,
                'resource_type' => $resourceType,
                'action' => $action,
                'target' => $target,
                'dependencies' => $operationDependencies,
                'warning_codes' => $warningCodes,
                'summary' => ucfirst($action) . ' ' . rtrim(str_replace('_', ' ', $resourceType), 's') . '.',
            ];
            $dependencies[$operationId] = $operationDependencies;
        }

        $executionOrder = $this->executionOrder($dependencies);
        $effects = [];
        foreach ($executionOrder as $operationId) {
            $effects[] = $effectsByOperation[$operationId];
        }
        $warnings = array_values(array_unique($warnings));
        sort($warnings);

        return [
            'version' => 1,
            'effects' => $effects,
            'warnings' => $warnings,
            'execution_order' => $executionOrder,
        ];
    }

    /**
     * @param  array<string, mixed>  $operation
     * @param  array<string, array{operation_id: string, resource_type: mixed}>  $localRefs
     * @return list<string>
     */
    private function localDependencies(array $operation, array $localRefs, int $index, string $operationId): array
    {
        $references = [];
        $walk = function (mixed $value, string $path) use (&$walk, &$references, $localRefs, $operationId): void {
            if ( ! is_array($value)) {
                return;
            }
            if (array_keys($value) === ['local_ref']) {
                $localRef = $value['local_ref'];
                if ( ! is_string($localRef) || ! isset($localRefs[$localRef])) {
                    throw new AgentApiException('local_reference_not_found', 'The local reference does not identify a create operation in this Change Set.', 422, $path . '/local_ref', $operationId);
                }
                if ($localRefs[$localRef]['operation_id'] === $operationId) {
                    throw new AgentApiException('dependency_cycle', 'An operation cannot depend on its own local reference.', 422, $path . '/local_ref', $operationId);
                }
                $references[] = $localRefs[$localRef]['operation_id'];

                return;
            }
            foreach ($value as $key => $child) {
                $walk($child, $path . '/' . (string) $key);
            }
        };
        $walk($operation, "/operations/{$index}");

        $references = array_values(array_unique($references));
        sort($references);

        return $references;
    }

    /**
     * @param  array<string, mixed>  $operation
     * @param  array<string, array<string, true>>  $pendingNames
     */
    private function validateOperation(
        AuthorizedFamilyContext $context,
        array $operation,
        string $resourceType,
        string $action,
        int $index,
        string $operationId,
        array &$pendingNames,
    ): void {
        if ($action !== 'create') {
            $this->validateExistingTarget($context, $operation, $resourceType, $action, $index, $operationId, $pendingNames);

            return;
        }

        $data = $operation['data'] ?? null;
        if ( ! is_array($data) || ($data !== [] && array_is_list($data))) {
            throw new AgentApiException('validation_failed', 'A create operation requires a data object.', 422, "/operations/{$index}/data", $operationId);
        }
        $data = $this->stringObject($data, "/operations/{$index}/data", $operationId);

        match ($resourceType) {
            'stores' => $this->validateNamedCreate($context, Store::class, $data, $resourceType, $index, $operationId, $pendingNames),
            'store_sections' => $this->validateStoreSectionCreate($context, $data, $index, $operationId, $pendingNames),
            'ingredients' => $this->validateIngredientCreate($context, $data, $index, $operationId, $pendingNames),
            'recipe_tags' => $this->validateNamedCreate($context, RecipeTag::class, $data, $resourceType, $index, $operationId, $pendingNames),
            'recipes' => $this->validateRecipeCreate($context, $data, $index, $operationId, $pendingNames),
            'calendar_entries' => $this->validateCalendarCreate($data, $index, $operationId),
            default => throw new LogicException('A validated resource type has no preview handler.'),
        };
    }

    /**
     * @param  array<string, mixed>  $operation
     * @param  array<string, array<string, true>>  $pendingNames
     */
    private function validateExistingTarget(AuthorizedFamilyContext $context, array $operation, string $resourceType, string $action, int $index, string $operationId, array &$pendingNames): void
    {
        $resourceId = $operation['resource_id'] ?? null;
        if ( ! is_int($resourceId) || $resourceId < 1) {
            throw new AgentApiException('validation_failed', 'An existing resource operation requires a positive integer resource_id.', 422, "/operations/{$index}/resource_id", $operationId);
        }
        $expected = $operation['expected_updated_at'] ?? null;
        if ( ! is_string($expected)) {
            throw new AgentApiException('validation_failed', 'An existing resource operation requires expected_updated_at.', 422, "/operations/{$index}/expected_updated_at", $operationId);
        }

        $model = $this->resourceModel($resourceType)::query()->where('family_id', $context->family->id)->find($resourceId);
        if ( ! $model instanceof Model) {
            throw new AgentApiException('family_scope_violation', 'The resource is unavailable in this Family.', 404, "/operations/{$index}/resource_id", $operationId);
        }
        $updatedAt = $model->getAttribute('updated_at');
        if ( ! $updatedAt instanceof CarbonInterface) {
            throw new LogicException('Agent resources require updated_at timestamps.');
        }
        $actual = $updatedAt->utc()->format('Y-m-d\TH:i:s\Z');
        if ($actual !== $expected) {
            throw new AgentApiException('stale_preview', 'The resource changed after the expected timestamp.', 409, "/operations/{$index}/expected_updated_at", $operationId, ['expected_updated_at' => $expected, 'actual_updated_at' => $actual]);
        }
        if ($action === 'update') {
            $set = $operation['set'] ?? [];
            $unset = $operation['unset'] ?? [];
            if ( ! is_array($set) || ($set !== [] && array_is_list($set)) || ! is_array($unset) || ! array_is_list($unset) || ($set === [] && $unset === [])) {
                throw new AgentApiException('validation_failed', 'An update requires a non-empty set object or unset list.', 422, "/operations/{$index}", $operationId);
            }
            foreach ($set as $field => $value) {
                if ($value === null) {
                    throw new AgentApiException('validation_failed', 'A set value cannot be null; use unset for an optional scalar.', 422, "/operations/{$index}/set/{$field}", $operationId);
                }
            }
            $this->validateUpdateFields($resourceType, $set, $unset, $index, $operationId);
            $this->validateUpdateValues($context, $model, $resourceType, $set, $index, $operationId, $pendingNames);
        }

        if (in_array($resourceType, ['ingredients', 'recipes'], true)) {
            $archivedAt = $model->getAttribute('archived_at');
            if (in_array($action, ['update', 'archive'], true) && $archivedAt !== null) {
                throw new AgentApiException('state_conflict', 'The resource must be active for this action.', 409, "/operations/{$index}/action", $operationId);
            }
            if ($action === 'restore' && $archivedAt === null) {
                throw new AgentApiException('state_conflict', 'The resource must be archived for this action.', 409, "/operations/{$index}/action", $operationId);
            }
        }
    }

    /**
     * @param  array<mixed, mixed>  $set
     * @param  array<string, array<string, true>>  $pendingNames
     */
    private function validateUpdateValues(AuthorizedFamilyContext $context, Model $model, string $resourceType, array $set, int $index, string $operationId, array &$pendingNames): void
    {
        if (array_key_exists('name', $set)) {
            if ( ! is_string($set['name'])) {
                throw new AgentApiException('validation_failed', 'The name must be a string.', 422, "/operations/{$index}/set/name", $operationId);
            }
            $name = NormalizedName::from($set['name']);
            $pendingNames[$resourceType] ??= [];
            $conflict = isset($pendingNames[$resourceType][$name->key]) || $this->resourceModel($resourceType)::query()
                ->where('family_id', $context->family->id)
                ->where('normalized_name', $name->key)
                ->whereKeyNot($model->getKey())
                ->exists();
            if ($conflict) {
                throw new AgentApiException('name_conflict', 'A resource with this normalized name already exists in the Family.', 409, "/operations/{$index}/set/name", $operationId, ['normalized_name' => $name->key]);
            }
            $pendingNames[$resourceType][$name->key] = true;
        }
        if ($resourceType === 'ingredients' && is_array($set['package_quantities'] ?? null)) {
            $this->validatePackageQuantities($set['package_quantities'], "/operations/{$index}/set/package_quantities", $operationId);
        }
        if ($resourceType === 'recipes') {
            if (isset($set['base_servings'])) {
                $this->validateDecimal($set['base_servings'], "/operations/{$index}/set/base_servings", $operationId, positive: true);
            }
            if (array_key_exists('ingredients', $set)) {
                $this->validateRecipeLines($set['ingredients'], "/operations/{$index}/set/ingredients", $operationId);
            }
        }
        if ($resourceType === 'calendar_entries' && isset($set['serving_count'])) {
            $this->validateServingCount($set['serving_count'], "/operations/{$index}/set/serving_count", $operationId);
        }
    }

    /**
     * @param  array<mixed, mixed>  $set
     * @param  array<mixed>  $unset
     */
    private function validateUpdateFields(string $resourceType, array $set, array $unset, int $index, string $operationId): void
    {
        $allowedSet = match ($resourceType) {
            'stores' => ['name', 'store_section_ids'],
            'store_sections' => ['name', 'colour', 'icon'],
            'ingredients' => ['name', 'description', 'package_quantities', 'nutrition_profile', 'store_placement', 'alternative_ingredient_ids'],
            'recipe_tags' => ['name'],
            'recipes' => ['name', 'base_servings', 'source_url', 'preparation_minutes', 'cooking_minutes', 'notes', 'ingredients', 'steps', 'recipe_tag_ids', 'nutrition_override'],
            'calendar_entries' => ['recipe_id', 'date', 'meal_label', 'serving_count'],
            default => throw new LogicException('A validated resource type has no update field contract.'),
        };
        $allowedUnset = match ($resourceType) {
            'ingredients' => ['description', 'nutrition_profile', 'store_placement'],
            'recipes' => ['source_url', 'preparation_minutes', 'cooking_minutes', 'notes', 'nutrition_override'],
            'calendar_entries' => ['meal_label'],
            default => [],
        };
        foreach (array_keys($set) as $field) {
            if ( ! is_string($field) || ! in_array($field, $allowedSet, true)) {
                throw new AgentApiException('validation_failed', 'The set object contains an unsupported field.', 422, "/operations/{$index}/set/" . (string) $field, $operationId);
            }
        }
        $seen = [];
        foreach ($unset as $offset => $field) {
            if ( ! is_string($field) || ! in_array($field, $allowedUnset, true)) {
                throw new AgentApiException('validation_failed', 'The unset list contains an unsupported field.', 422, "/operations/{$index}/unset/{$offset}", $operationId);
            }
            if (array_key_exists($field, $set) || isset($seen[$field])) {
                throw new AgentApiException('validation_failed', 'A field cannot be both set and unset or repeated.', 422, "/operations/{$index}/unset/{$offset}", $operationId);
            }
            $seen[$field] = true;
        }
    }

    /**
     * @param  array<string, mixed>  $operation
     * @param  array<string, array{operation_id: string, resource_type: mixed}>  $localRefs
     */
    private function validateRelationshipScope(
        AuthorizedFamilyContext $context,
        array $operation,
        string $resourceType,
        string $action,
        array $localRefs,
        int $index,
        string $operationId,
    ): void {
        if ( ! in_array($action, ['create', 'update'], true)) {
            return;
        }
        $source = $action === 'create' ? ($operation['data'] ?? []) : ($operation['set'] ?? []);
        if ( ! is_array($source)) {
            return;
        }
        $base = "/operations/{$index}/" . ($action === 'create' ? 'data' : 'set');

        if ($resourceType === 'stores' && array_key_exists('store_section_ids', $source)) {
            $this->validateReferenceList($context, $source['store_section_ids'], 'store_sections', $localRefs, $base . '/store_section_ids', $operationId);
        }
        if ($resourceType === 'ingredients') {
            $placement = $source['store_placement'] ?? null;
            if (is_array($placement)) {
                foreach (['store_id' => 'stores', 'store_section_id' => 'store_sections'] as $field => $expectedType) {
                    if (($placement[$field] ?? null) !== null) {
                        $this->validateReference($context, $placement[$field], $expectedType, $localRefs, $base . "/store_placement/{$field}", $operationId);
                    }
                }
            }
            if (array_key_exists('alternative_ingredient_ids', $source)) {
                $this->validateReferenceList($context, $source['alternative_ingredient_ids'], 'ingredients', $localRefs, $base . '/alternative_ingredient_ids', $operationId);
            }
        }
        if ($resourceType === 'recipes') {
            $ingredients = $source['ingredients'] ?? null;
            if (is_array($ingredients) && array_is_list($ingredients)) {
                foreach ($ingredients as $lineIndex => $line) {
                    if (is_array($line)) {
                        $this->validateReference($context, $line['ingredient_id'] ?? null, 'ingredients', $localRefs, $base . "/ingredients/{$lineIndex}/ingredient_id", $operationId, activeOnly: true);
                    }
                }
            }
            if (array_key_exists('recipe_tag_ids', $source)) {
                $this->validateReferenceList($context, $source['recipe_tag_ids'], 'recipe_tags', $localRefs, $base . '/recipe_tag_ids', $operationId);
            }
        }
        if ($resourceType === 'calendar_entries' && array_key_exists('recipe_id', $source)) {
            $this->validateReference($context, $source['recipe_id'], 'recipes', $localRefs, $base . '/recipe_id', $operationId, activeOnly: true);
        }
    }

    /** @param array<string, array{operation_id: string, resource_type: mixed}> $localRefs */
    private function validateReferenceList(AuthorizedFamilyContext $context, mixed $references, string $expectedType, array $localRefs, string $path, string $operationId): void
    {
        if ( ! is_array($references) || ! array_is_list($references)) {
            throw new AgentApiException('validation_failed', 'A relationship replacement must be a JSON array.', 422, $path, $operationId);
        }
        foreach ($references as $offset => $reference) {
            $this->validateReference($context, $reference, $expectedType, $localRefs, "{$path}/{$offset}", $operationId);
        }
    }

    /** @param array<string, array{operation_id: string, resource_type: mixed}> $localRefs */
    private function validateReference(AuthorizedFamilyContext $context, mixed $reference, string $expectedType, array $localRefs, string $path, string $operationId, bool $activeOnly = false): void
    {
        if (is_array($reference) && array_keys($reference) === ['local_ref']) {
            $localRef = $reference['local_ref'];
            if ( ! is_string($localRef) || ! isset($localRefs[$localRef]) || $localRefs[$localRef]['resource_type'] !== $expectedType) {
                throw new AgentApiException('local_reference_type_mismatch', 'The local reference has the wrong resource type.', 422, $path, $operationId, ['expected_resource_type' => $expectedType]);
            }

            return;
        }
        if ( ! is_int($reference) || $reference < 1) {
            throw new AgentApiException('validation_failed', 'An existing relationship reference must be a positive integer identifier.', 422, $path, $operationId);
        }
        $query = $this->resourceModel($expectedType)::query()
            ->where('family_id', $context->family->id)
            ->whereKey($reference);
        if ($activeOnly) {
            $query->whereNull('archived_at');
        }
        if ( ! $query->exists()) {
            throw new AgentApiException('family_scope_violation', 'The related resource is unavailable in this Family.', 404, $path, $operationId);
        }
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, mixed>  $data
     * @param  array<string, array<string, true>>  $pendingNames
     */
    private function validateNamedCreate(AuthorizedFamilyContext $context, string $modelClass, array $data, string $resourceType, int $index, string $operationId, array &$pendingNames): void
    {
        $name = NormalizedName::from($this->requiredString($data, 'name', "/operations/{$index}/data/name", $operationId));
        if ($name->display === '' || mb_strlen($name->display) > 255) {
            throw new AgentApiException('validation_failed', 'The name must contain between 1 and 255 characters.', 422, "/operations/{$index}/data/name", $operationId);
        }
        $pendingNames[$resourceType] ??= [];
        $exists = isset($pendingNames[$resourceType][$name->key]) || $modelClass::query()
            ->where('family_id', $context->family->id)
            ->where('normalized_name', $name->key)
            ->exists();
        if ($exists) {
            throw new AgentApiException('name_conflict', 'A resource with this normalized name already exists in the Family.', 409, "/operations/{$index}/data/name", $operationId, ['normalized_name' => $name->key]);
        }
        $pendingNames[$resourceType][$name->key] = true;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, array<string, true>>  $pendingNames
     */
    private function validateStoreSectionCreate(AuthorizedFamilyContext $context, array $data, int $index, string $operationId, array &$pendingNames): void
    {
        $this->validateNamedCreate($context, StoreSection::class, $data, 'store_sections', $index, $operationId, $pendingNames);
        $colour = $this->requiredString($data, 'colour', "/operations/{$index}/data/colour", $operationId);
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $colour) !== 1) {
            throw new AgentApiException('validation_failed', 'The colour must be a six-digit hexadecimal colour.', 422, "/operations/{$index}/data/colour", $operationId);
        }
        if ( ! is_string($data['icon'] ?? null) || StoreSectionIcon::tryFrom($data['icon']) === null) {
            throw new AgentApiException('validation_failed', 'The icon is not supported.', 422, "/operations/{$index}/data/icon", $operationId);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, array<string, true>>  $pendingNames
     */
    private function validateIngredientCreate(AuthorizedFamilyContext $context, array $data, int $index, string $operationId, array &$pendingNames): void
    {
        $this->validateNamedCreate($context, Ingredient::class, $data, 'ingredients', $index, $operationId, $pendingNames);
        if ( ! is_array($data['package_quantities'] ?? null)) {
            throw new AgentApiException('validation_failed', 'An Ingredient requires package_quantities.', 422, "/operations/{$index}/data/package_quantities", $operationId);
        }
        $this->validatePackageQuantities($data['package_quantities'], "/operations/{$index}/data/package_quantities", $operationId);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, array<string, true>>  $pendingNames
     */
    private function validateRecipeCreate(AuthorizedFamilyContext $context, array $data, int $index, string $operationId, array &$pendingNames): void
    {
        $this->validateNamedCreate($context, Recipe::class, $data, 'recipes', $index, $operationId, $pendingNames);
        foreach (['base_servings', 'ingredients', 'steps', 'recipe_tag_ids'] as $field) {
            if ( ! array_key_exists($field, $data)) {
                throw new AgentApiException('validation_failed', "A Recipe requires {$field}.", 422, "/operations/{$index}/data/{$field}", $operationId);
            }
        }
        $this->validateDecimal($data['base_servings'], "/operations/{$index}/data/base_servings", $operationId, positive: true);
        $this->validateRecipeLines($data['ingredients'], "/operations/{$index}/data/ingredients", $operationId);
    }

    /** @param array<string, mixed> $data */
    private function validateCalendarCreate(array $data, int $index, string $operationId): void
    {
        foreach (['recipe_id', 'date', 'serving_count'] as $field) {
            if ( ! array_key_exists($field, $data)) {
                throw new AgentApiException('validation_failed', "A Calendar Entry requires {$field}.", 422, "/operations/{$index}/data/{$field}", $operationId);
            }
        }
        if ( ! is_string($data['date']) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date']) !== 1) {
            throw new AgentApiException('validation_failed', 'The date must use YYYY-MM-DD.', 422, "/operations/{$index}/data/date", $operationId);
        }
        [$year, $month, $day] = array_map('intval', explode('-', $data['date']));
        if ( ! checkdate($month, $day, $year)) {
            throw new AgentApiException('validation_failed', 'The date must be a real calendar date.', 422, "/operations/{$index}/data/date", $operationId);
        }
        $mealLabel = $data['meal_label'] ?? null;
        if ($mealLabel !== null && ( ! is_string($mealLabel) || MealLabel::tryFrom($mealLabel) === null)) {
            throw new AgentApiException('validation_failed', 'The meal_label is not supported.', 422, "/operations/{$index}/data/meal_label", $operationId);
        }
        $this->validateServingCount($data['serving_count'], "/operations/{$index}/data/serving_count", $operationId);
    }

    private function validatePackageQuantities(mixed $value, string $path, string $operationId): void
    {
        if ( ! is_array($value) || array_is_list($value)) {
            throw new AgentApiException('validation_failed', 'package_quantities must be a JSON object.', 422, $path, $operationId);
        }
        $present = false;
        foreach (['weight_grams', 'volume_millilitres', 'piece_count'] as $field) {
            $quantity = $value[$field] ?? null;
            if ($quantity !== null) {
                $present = true;
                $this->validateDecimal($quantity, "{$path}/{$field}", $operationId, positive: true);
            }
        }
        if ( ! $present) {
            throw new AgentApiException('validation_failed', 'At least one package quantity is required.', 422, $path, $operationId);
        }
    }

    private function validateRecipeLines(mixed $value, string $path, string $operationId): void
    {
        if ( ! is_array($value) || ! array_is_list($value) || $value === []) {
            throw new AgentApiException('validation_failed', 'A Recipe requires at least one Ingredient line.', 422, $path, $operationId);
        }
        foreach ($value as $index => $line) {
            if ( ! is_array($line) || ! in_array($line['quantity_kind'] ?? null, ['grams', 'millilitres', 'piece'], true)) {
                throw new AgentApiException('validation_failed', 'Each Recipe Ingredient line requires a supported quantity_kind.', 422, "{$path}/{$index}", $operationId);
            }
            $this->validateDecimal($line['quantity'] ?? null, "{$path}/{$index}/quantity", $operationId, positive: true);
        }
    }

    private function validateServingCount(mixed $value, string $path, string $operationId): void
    {
        try {
            if ( ! is_string($value)) {
                throw new LogicException();
            }
            ServingCount::from($value);
        } catch (Throwable) {
            throw new AgentApiException('validation_failed', 'serving_count must be a positive canonical decimal.', 422, $path, $operationId);
        }
    }

    private function validateDecimal(mixed $value, string $path, string $operationId, bool $positive): void
    {
        if ( ! is_string($value) || preg_match('/^(?:0|[1-9]\d{0,13})(?:\.\d{1,6})?$/', $value) !== 1) {
            throw new AgentApiException('validation_failed', 'The value must be a canonical decimal with at most six fractional places.', 422, $path, $operationId);
        }
        if ($positive && BigDecimal::of($value)->isLessThanOrEqualTo(0)) {
            throw new AgentApiException('validation_failed', 'The decimal value must be positive.', 422, $path, $operationId);
        }
    }

    private function validateSupportedAction(string $resourceType, string $action, int $index, string $operationId): void
    {
        $resource = CatalogResourceType::tryFrom($resourceType);
        $supported = $resource instanceof CatalogResourceType
            && in_array($action, self::SUPPORTED_ACTIONS, true)
            && match ($resource) {
                CatalogResourceType::Stores, CatalogResourceType::StoreSections, CatalogResourceType::RecipeTags, CatalogResourceType::CalendarEntries => in_array($action, ['create', 'update', 'delete'], true),
                CatalogResourceType::Ingredients, CatalogResourceType::Recipes => in_array($action, ['create', 'update', 'archive', 'restore'], true),
            };
        if ( ! $supported) {
            throw new AgentApiException('validation_failed', 'The requested resource action is not supported by this API version.', 422, "/operations/{$index}/action", $operationId, ['resource_type' => $resourceType, 'action' => $action]);
        }
    }

    /** @return class-string<Model> */
    private function resourceModel(string $resourceType): string
    {
        return match ($resourceType) {
            'stores' => Store::class,
            'store_sections' => StoreSection::class,
            'ingredients' => Ingredient::class,
            'recipe_tags' => RecipeTag::class,
            'recipes' => Recipe::class,
            'calendar_entries' => CalendarEntry::class,
            default => throw new LogicException('A validated resource type has no model.'),
        };
    }

    /** @return list<string> */
    private function warningCodes(string $resourceType, string $action): array
    {
        return match ([$resourceType, $action]) {
            ['stores', 'delete'] => ['store_delete'],
            ['store_sections', 'delete'] => ['store_section_delete'],
            ['ingredients', 'archive'] => ['ingredient_archive'],
            ['recipe_tags', 'delete'] => ['recipe_tag_delete'],
            ['recipes', 'archive'] => ['recipe_archive'],
            ['calendar_entries', 'delete'] => ['calendar_entry_delete'],
            default => [],
        };
    }

    /**
     * @param  array<string, list<string>>  $dependencies
     * @return list<string>
     */
    private function executionOrder(array $dependencies): array
    {
        $remaining = $dependencies;
        $ordered = [];
        while ($remaining !== []) {
            $ready = [];
            foreach ($remaining as $operationId => $required) {
                if (array_diff($required, $ordered) === []) {
                    $ready[] = $operationId;
                }
            }
            sort($ready);
            if ($ready === []) {
                throw new AgentApiException('dependency_cycle', 'The local-reference graph contains a dependency cycle.', 422, '/operations');
            }
            $operationId = $ready[0];
            $ordered[] = $operationId;
            unset($remaining[$operationId]);
        }

        return $ordered;
    }

    /**
     * @param  array<mixed, mixed>  $value
     * @return array<string, mixed>
     */
    private function stringObject(array $value, string $path, string $operationId): array
    {
        $object = [];
        foreach ($value as $key => $child) {
            if ( ! is_string($key)) {
                throw new AgentApiException('validation_failed', 'A JSON object must use string field names.', 422, $path, $operationId);
            }
            $object[$key] = $child;
        }

        return $object;
    }

    private function authorize(AgentCredential $credential, string $resourceType, string $action, string $operationId): void
    {
        $required = $resourceType === 'calendar_entries' ? AgentCredentialAbility::PlanningWrite : AgentCredentialAbility::CookbookWrite;
        if ( ! $credential->can($required->value)) {
            throw new AgentApiException('ability_required', 'The Agent Credential lacks the ability required by this operation.', 403, operationId: $operationId, details: ['required_abilities' => [$required->value]]);
        }
        if (in_array($action, ['archive', 'delete'], true) && ! $credential->can(AgentCredentialAbility::DestructiveWrite->value)) {
            throw new AgentApiException('ability_required', 'The Agent Credential lacks the ability required by this destructive operation.', 403, operationId: $operationId, details: ['required_abilities' => [AgentCredentialAbility::DestructiveWrite->value]]);
        }
    }

    /** @param array<mixed, mixed> $document */
    private function requiredString(array $document, string $key, string $path, ?string $operationId = null): string
    {
        $value = $document[$key] ?? null;
        if ( ! is_string($value) || trim($value) === '') {
            throw new AgentApiException('validation_failed', "The {$key} field must be a non-empty string.", 422, $path, $operationId);
        }

        return $value;
    }
}
