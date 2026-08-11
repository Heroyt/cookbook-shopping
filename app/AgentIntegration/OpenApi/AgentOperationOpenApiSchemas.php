<?php

declare(strict_types=1);

namespace App\AgentIntegration\OpenApi;

use App\AgentIntegration\OpenApi\Types\ContractObjectType;
use App\AgentIntegration\OpenApi\Types\OneOfType;
use App\Cookbook\Values\StoreSectionIcon;
use App\MealPlanning\Values\MealLabel;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\NullType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Generator\Types\Type;
use LogicException;

final readonly class AgentOperationOpenApiSchemas
{
    private const CANONICAL_DECIMAL_PATTERN = '^(?:0|[1-9]\\d{0,13})(?:\\.\\d{1,6})?$';

    private const POSITIVE_DECIMAL_PATTERN = '^(?:[1-9]\\d{0,13}(?:\\.\\d{1,6})?|0\\.(?=\\d*[1-9])\\d{1,6})$';

    public function register(OpenApi $document): Reference
    {
        $operations = [];
        foreach ($this->operationSchemas() as $name => $schema) {
            $operations[] = $this->addSchema($document, $name, $schema);
        }

        return $this->addSchema($document, 'AgentChangeSetOperation', new OneOfType($operations));
    }

    /** @return list<array<string, mixed>> */
    public function documentExamples(): array
    {
        $timestamp = '2026-08-12T10:00:00Z';

        return [
            $this->document('stores-create', $this->createExample('stores', 'store', ['name' => 'Testovací obchod', 'store_section_ids' => []])),
            $this->document('stores-update', $this->updateExample('stores', 101, $timestamp, ['name' => 'Přejmenovaný obchod'])),
            $this->document('stores-delete', $this->existingExample('stores', 'delete', 101, $timestamp)),
            $this->document('store-sections-create', $this->createExample('store_sections', 'section', ['name' => 'Zelenina', 'colour' => '#22c55e', 'icon' => 'carrot'])),
            $this->document('store-sections-update', $this->updateExample('store_sections', 102, $timestamp, ['colour' => '#3b82f6'])),
            $this->document('store-sections-delete', $this->existingExample('store_sections', 'delete', 102, $timestamp)),
            $this->document('ingredients-create', $this->createExample('ingredients', 'ingredient', [
                'name' => 'Rajčata',
                'description' => null,
                'package_quantities' => ['weight_grams' => '1000', 'volume_millilitres' => null, 'piece_count' => '8'],
                'nutrition_profile' => null,
                'store_placement' => ['store_id' => null, 'store_section_id' => null],
                'alternative_ingredient_ids' => [],
            ])),
            $this->document('ingredients-update', $this->updateExample('ingredients', 103, $timestamp, [], ['description'])),
            $this->document('ingredients-archive', $this->existingExample('ingredients', 'archive', 103, $timestamp)),
            $this->document('ingredients-restore', $this->existingExample('ingredients', 'restore', 103, $timestamp)),
            $this->document('recipe-tags-create', $this->createExample('recipe_tags', 'tag', ['name' => 'Rychlé'])),
            $this->document('recipe-tags-update', $this->updateExample('recipe_tags', 104, $timestamp, ['name' => 'Do půl hodiny'])),
            $this->document('recipe-tags-delete', $this->existingExample('recipe_tags', 'delete', 104, $timestamp)),
            $this->document('recipes-create', $this->createExample('recipes', 'recipe', [
                'name' => 'Rajčatová polévka',
                'base_servings' => '4',
                'source_url' => 'https://example.test/recept',
                'preparation_minutes' => 10,
                'cooking_minutes' => 25,
                'notes' => null,
                'ingredients' => [['ingredient_id' => 103, 'quantity' => '2', 'quantity_kind' => 'piece']],
                'steps' => ['Uvařit.'],
                'recipe_tag_ids' => [104],
                'nutrition_override' => null,
            ])),
            $this->document('recipes-update', $this->updateExample('recipes', 105, $timestamp, ['notes' => 'Podávat teplé.'])),
            $this->document('recipes-archive', $this->existingExample('recipes', 'archive', 105, $timestamp)),
            $this->document('recipes-restore', $this->existingExample('recipes', 'restore', 105, $timestamp)),
            $this->document('calendar-entries-create', $this->createExample('calendar_entries', 'calendar-entry', [
                'recipe_id' => 105,
                'date' => '2026-08-20',
                'meal_label' => 'večeře',
                'serving_count' => '2',
            ])),
            $this->document('calendar-entries-update', $this->updateExample('calendar_entries', 106, $timestamp, [], ['meal_label'])),
            $this->document('calendar-entries-delete', $this->existingExample('calendar_entries', 'delete', 106, $timestamp)),
        ];
    }

    /** @return array<string, ContractObjectType> */
    private function operationSchemas(): array
    {
        return [
            'CreateStoreOperation' => $this->createOperation('stores', $this->storeData()),
            'UpdateStoreOperation' => $this->updateOperation('stores', $this->storeSet()),
            'DeleteStoreOperation' => $this->existingOperation('stores', 'delete'),
            'CreateStoreSectionOperation' => $this->createOperation('store_sections', $this->storeSectionData()),
            'UpdateStoreSectionOperation' => $this->updateOperation('store_sections', $this->storeSectionSet()),
            'DeleteStoreSectionOperation' => $this->existingOperation('store_sections', 'delete'),
            'CreateIngredientOperation' => $this->createOperation('ingredients', $this->ingredientData()),
            'UpdateIngredientOperation' => $this->updateOperation('ingredients', $this->ingredientSet(), ['description', 'nutrition_profile', 'store_placement']),
            'ArchiveIngredientOperation' => $this->existingOperation('ingredients', 'archive'),
            'RestoreIngredientOperation' => $this->existingOperation('ingredients', 'restore'),
            'CreateRecipeTagOperation' => $this->createOperation('recipe_tags', $this->namedData()),
            'UpdateRecipeTagOperation' => $this->updateOperation('recipe_tags', $this->namedSet()),
            'DeleteRecipeTagOperation' => $this->existingOperation('recipe_tags', 'delete'),
            'CreateRecipeOperation' => $this->createOperation('recipes', $this->recipeData()),
            'UpdateRecipeOperation' => $this->updateOperation('recipes', $this->recipeSet(), ['source_url', 'preparation_minutes', 'cooking_minutes', 'notes', 'nutrition_override']),
            'ArchiveRecipeOperation' => $this->existingOperation('recipes', 'archive'),
            'RestoreRecipeOperation' => $this->existingOperation('recipes', 'restore'),
            'CreateCalendarEntryOperation' => $this->createOperation('calendar_entries', $this->calendarEntryData()),
            'UpdateCalendarEntryOperation' => $this->updateOperation('calendar_entries', $this->calendarEntrySet(), ['meal_label']),
            'DeleteCalendarEntryOperation' => $this->existingOperation('calendar_entries', 'delete'),
        ];
    }

    private function createOperation(string $resourceType, ContractObjectType $data): ContractObjectType
    {
        $schema = $this->operationEnvelope($resourceType, 'create');
        $schema->addProperty('local_ref', $this->nonEmptyString(max: 255));
        $schema->addProperty('data', $data);
        $schema->setRequired(['operation_id', 'resource_type', 'action', 'local_ref', 'data']);

        return $schema;
    }

    /** @param list<string> $unsetFields */
    private function updateOperation(string $resourceType, ContractObjectType $set, array $unsetFields = []): ContractObjectType
    {
        $schema = $this->operationEnvelope($resourceType, 'update');
        $schema->addProperty('resource_id', $this->positiveInteger());
        $schema->addProperty('expected_updated_at', $this->dateTime());
        $schema->addProperty('set', $set->requireAtLeastProperties(1));
        if ($unsetFields !== []) {
            $schema->addProperty('unset', $this->arrayOf($this->enumString($unsetFields), min: 1, unique: true));
            $schema->requireAnyOf([['set'], ['unset']]);
        } else {
            $schema->setRequired(['operation_id', 'resource_type', 'action', 'resource_id', 'expected_updated_at', 'set']);
        }

        return $schema;
    }

    private function existingOperation(string $resourceType, string $action): ContractObjectType
    {
        $schema = $this->operationEnvelope($resourceType, $action);
        $schema->addProperty('resource_id', $this->positiveInteger());
        $schema->addProperty('expected_updated_at', $this->dateTime());
        $schema->setRequired(['operation_id', 'resource_type', 'action', 'resource_id', 'expected_updated_at']);

        return $schema;
    }

    private function operationEnvelope(string $resourceType, string $action): ContractObjectType
    {
        $schema = new ContractObjectType();
        $schema->addProperty('operation_id', $this->nonEmptyString(max: 255));
        $schema->addProperty('resource_type', $this->constString($resourceType));
        $schema->addProperty('action', $this->constString($action));
        $schema->setRequired(['operation_id', 'resource_type', 'action', 'resource_id', 'expected_updated_at']);

        return $schema;
    }

    private function storeData(): ContractObjectType
    {
        $schema = $this->namedData();
        $schema->addProperty('store_section_ids', $this->referenceList());

        return $schema;
    }

    private function storeSet(): ContractObjectType
    {
        $schema = $this->namedSet();
        $schema->addProperty('store_section_ids', $this->referenceList());

        return $schema;
    }

    private function storeSectionData(): ContractObjectType
    {
        $schema = $this->namedData();
        $schema->addProperty('colour', $this->colour());
        $schema->addProperty('icon', $this->storeSectionIcon());
        $schema->setRequired(['name', 'colour', 'icon']);

        return $schema;
    }

    private function storeSectionSet(): ContractObjectType
    {
        $schema = $this->namedSet();
        $schema->addProperty('colour', $this->colour());
        $schema->addProperty('icon', $this->storeSectionIcon());

        return $schema;
    }

    private function ingredientData(): ContractObjectType
    {
        $schema = $this->namedData();
        $schema->addProperty('description', $this->nullable($this->string()));
        $schema->addProperty('package_quantities', $this->packageQuantities());
        $schema->addProperty('nutrition_profile', $this->nullable($this->ingredientNutrition()));
        $schema->addProperty('store_placement', $this->storePlacement());
        $schema->addProperty('alternative_ingredient_ids', $this->referenceList());
        $schema->setRequired(['name', 'package_quantities', 'store_placement']);

        return $schema;
    }

    private function ingredientSet(): ContractObjectType
    {
        $schema = $this->namedSet();
        $schema->addProperty('description', $this->string());
        $schema->addProperty('package_quantities', $this->packageQuantities());
        $schema->addProperty('nutrition_profile', $this->ingredientNutrition());
        $schema->addProperty('store_placement', $this->storePlacement());
        $schema->addProperty('alternative_ingredient_ids', $this->referenceList());

        return $schema;
    }

    private function recipeData(): ContractObjectType
    {
        $schema = $this->namedData();
        $this->addRecipeFields($schema, nullableOptionals: true);
        $schema->setRequired(['name', 'base_servings', 'ingredients', 'steps', 'recipe_tag_ids']);

        return $schema;
    }

    private function recipeSet(): ContractObjectType
    {
        $schema = $this->namedSet();
        $this->addRecipeFields($schema, nullableOptionals: false);

        return $schema;
    }

    private function addRecipeFields(ContractObjectType $schema, bool $nullableOptionals): void
    {
        $schema->addProperty('base_servings', $this->positiveDecimal());
        $schema->addProperty('source_url', $nullableOptionals ? $this->nullable($this->uri()) : $this->uri());
        $schema->addProperty('preparation_minutes', $nullableOptionals ? $this->nullable($this->nonNegativeInteger()) : $this->nonNegativeInteger());
        $schema->addProperty('cooking_minutes', $nullableOptionals ? $this->nullable($this->nonNegativeInteger()) : $this->nonNegativeInteger());
        $schema->addProperty('notes', $nullableOptionals ? $this->nullable($this->string()) : $this->string());
        $schema->addProperty('ingredients', $this->arrayOf($this->recipeIngredient(), min: 1));
        $schema->addProperty('steps', $this->arrayOf($this->nonEmptyString()));
        $schema->addProperty('recipe_tag_ids', $this->referenceList());
        $schema->addProperty('nutrition_override', $nullableOptionals ? $this->nullable($this->recipeNutrition()) : $this->recipeNutrition());
    }

    private function calendarEntryData(): ContractObjectType
    {
        $schema = $this->calendarEntrySet();
        $schema->addProperty('meal_label', $this->nullable($this->mealLabel()));
        $schema->setRequired(['recipe_id', 'date', 'serving_count']);

        return $schema;
    }

    private function calendarEntrySet(): ContractObjectType
    {
        $schema = new ContractObjectType();
        $schema->addProperty('recipe_id', $this->resourceReference());
        $schema->addProperty('date', $this->date());
        $schema->addProperty('meal_label', $this->mealLabel());
        $schema->addProperty('serving_count', $this->positiveDecimal());

        return $schema;
    }

    private function namedData(): ContractObjectType
    {
        $schema = $this->namedSet();
        $schema->setRequired(['name']);

        return $schema;
    }

    private function namedSet(): ContractObjectType
    {
        $schema = new ContractObjectType();
        $schema->addProperty('name', $this->nonEmptyString(max: 255));

        return $schema;
    }

    private function packageQuantities(): ContractObjectType
    {
        $schema = new ContractObjectType();
        $schema->addProperty('weight_grams', $this->nullable($this->positiveDecimal()));
        $schema->addProperty('volume_millilitres', $this->nullable($this->positiveDecimal()));
        $schema->addProperty('piece_count', $this->nullable($this->positiveDecimal()));
        $schema->setDescription('The complete package quantities. At least one value must be non-null; weight and volume are mutually exclusive.');

        return $schema;
    }

    private function storePlacement(): ContractObjectType
    {
        $schema = new ContractObjectType();
        $schema->addProperty('store_id', $this->nullable($this->resourceReference()));
        $schema->addProperty('store_section_id', $this->nullable($this->resourceReference()));
        $schema->setRequired(['store_id', 'store_section_id']);

        return $schema;
    }

    private function ingredientNutrition(): ContractObjectType
    {
        $schema = new ContractObjectType();
        $schema->addProperty('basis_kind', $this->enumString(['package', 'grams', 'millilitres', 'piece']));
        $schema->addProperty('basis_quantity', $this->positiveDecimal());
        foreach (['energy_kcal', 'fat_grams', 'protein_grams', 'carbohydrate_grams'] as $field) {
            $schema->addProperty($field, $this->canonicalDecimal());
        }
        $schema->setRequired(['basis_kind', 'basis_quantity', 'energy_kcal', 'fat_grams', 'protein_grams', 'carbohydrate_grams']);

        return $schema;
    }

    private function recipeNutrition(): ContractObjectType
    {
        $schema = new ContractObjectType();
        foreach (['energy_kcal', 'fat_grams', 'protein_grams', 'carbohydrate_grams'] as $field) {
            $schema->addProperty($field, $this->canonicalDecimal());
        }
        $schema->setRequired(['energy_kcal', 'fat_grams', 'protein_grams', 'carbohydrate_grams']);

        return $schema;
    }

    private function recipeIngredient(): ContractObjectType
    {
        $schema = new ContractObjectType();
        $schema->addProperty('ingredient_id', $this->resourceReference());
        $schema->addProperty('quantity', $this->positiveDecimal());
        $schema->addProperty('quantity_kind', $this->enumString(['grams', 'millilitres', 'piece']));
        $schema->setRequired(['ingredient_id', 'quantity', 'quantity_kind']);

        return $schema;
    }

    private function resourceReference(): OneOfType
    {
        $local = new ContractObjectType();
        $local->addProperty('local_ref', $this->nonEmptyString(max: 255));
        $local->setRequired(['local_ref']);

        return new OneOfType([$this->positiveInteger(), $local]);
    }

    private function referenceList(): ArrayType
    {
        return $this->arrayOf($this->resourceReference(), unique: true);
    }

    private function canonicalDecimal(): StringType
    {
        return $this->string(pattern: self::CANONICAL_DECIMAL_PATTERN);
    }

    private function positiveDecimal(): StringType
    {
        return $this->string(pattern: self::POSITIVE_DECIMAL_PATTERN);
    }

    private function colour(): StringType
    {
        return $this->string(pattern: '^#[0-9a-fA-F]{6}$');
    }

    private function storeSectionIcon(): StringType
    {
        return $this->enumString(array_map(
            fn (StoreSectionIcon $icon): string => $icon->value,
            StoreSectionIcon::cases(),
        ));
    }

    private function mealLabel(): StringType
    {
        return $this->enumString(array_map(
            fn (MealLabel $label): string => $label->value,
            MealLabel::cases(),
        ));
    }

    /** @param list<string> $values */
    private function enumString(array $values): StringType
    {
        $type = new StringType();
        $type->enum($values);

        return $type;
    }

    private function constString(string $value): StringType
    {
        $type = new StringType();
        $type->const($value);

        return $type;
    }

    private function nonEmptyString(?int $max = null): StringType
    {
        return $this->string(min: 1, max: $max);
    }

    private function uri(): StringType
    {
        return $this->string(format: 'uri', max: 2048);
    }

    private function date(): StringType
    {
        return $this->string(format: 'date', pattern: '^\\d{4}-\\d{2}-\\d{2}$');
    }

    private function dateTime(): StringType
    {
        return $this->string(format: 'date-time');
    }

    private function positiveInteger(): IntegerType
    {
        $type = new IntegerType();
        $type->setMin(1);

        return $type;
    }

    private function nonNegativeInteger(): IntegerType
    {
        $type = new IntegerType();
        $type->setMin(0);

        return $type;
    }

    private function nullable(Type $type): OneOfType
    {
        return new OneOfType([$type, new NullType()]);
    }

    private function string(
        string $format = '',
        ?int $min = null,
        ?int $max = null,
        ?string $pattern = null,
    ): StringType {
        $type = new StringType();
        if ($format !== '') {
            $type->format($format);
        }
        if ($min !== null) {
            $type->setMin($min);
        }
        if ($max !== null) {
            $type->setMax($max);
        }
        $type->pattern($pattern);

        return $type;
    }

    private function arrayOf(Type $items, ?int $min = null, bool $unique = false): ArrayType
    {
        $type = new ArrayType();
        $type->setItems($items);
        if ($min !== null) {
            $type->setMin($min);
        }
        $type->setUniqueItems($unique);

        return $type;
    }

    private function addSchema(OpenApi $document, string $name, Type $type): Reference
    {
        $schema = Schema::fromType($type);
        if ( ! $schema instanceof Schema) {
            throw new LogicException('Scramble did not create an OpenAPI schema.');
        }

        return $document->components->addSchema($name, $schema);
    }

    /**
     * @param  array<string, mixed>  $operation
     * @return array<string, mixed>
     */
    private function document(string $requestId, array $operation): array
    {
        return [
            'version' => 1,
            'client_request_id' => 'example-' . $requestId,
            'operations' => [$operation],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function createExample(string $resourceType, string $localRef, array $data): array
    {
        return [
            'operation_id' => $resourceType . '-create',
            'resource_type' => $resourceType,
            'action' => 'create',
            'local_ref' => $localRef,
            'data' => $data,
        ];
    }

    /**
     * @param  array<string, mixed>  $set
     * @param  list<string>  $unset
     * @return array<string, mixed>
     */
    private function updateExample(string $resourceType, int $resourceId, string $timestamp, array $set, array $unset = []): array
    {
        return [
            'operation_id' => $resourceType . '-update',
            'resource_type' => $resourceType,
            'action' => 'update',
            'resource_id' => $resourceId,
            'expected_updated_at' => $timestamp,
            ...($set === [] ? [] : ['set' => $set]),
            ...($unset === [] ? [] : ['unset' => $unset]),
        ];
    }

    /** @return array<string, mixed> */
    private function existingExample(string $resourceType, string $action, int $resourceId, string $timestamp): array
    {
        return [
            'operation_id' => $resourceType . '-' . $action,
            'resource_type' => $resourceType,
            'action' => $action,
            'resource_id' => $resourceId,
            'expected_updated_at' => $timestamp,
        ];
    }
}
