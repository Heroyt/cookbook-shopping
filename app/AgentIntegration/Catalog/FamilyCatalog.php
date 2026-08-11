<?php

declare(strict_types=1);

namespace App\AgentIntegration\Catalog;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\IngredientNutritionProfile;
use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\RecipeIngredient;
use App\Cookbook\Models\RecipeStep;
use App\Cookbook\Models\RecipeTag;
use App\Cookbook\Models\Store;
use App\Cookbook\Models\StoreSection;
use App\FamilyAccess\AuthorizedFamilyContext;
use App\MealPlanning\Models\CalendarEntry;
use App\MealPlanning\Values\MealLabel;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use LogicException;

final readonly class FamilyCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public function list(
        AuthorizedFamilyContext $context,
        ?CatalogResourceType $resourceType = null,
        ?string $status = null,
    ): array {
        $resources = [];
        $resourceTypes = $resourceType instanceof CatalogResourceType
            ? [$resourceType]
            : CatalogResourceType::cases();

        foreach ($resourceTypes as $type) {
            array_push($resources, ...$this->resources($context, $type));
        }

        if ($status !== null) {
            $resources = array_values(array_filter(
                $resources,
                static fn (array $resource): bool => $resource['status'] === $status,
            ));
        }

        return $resources;
    }

    /** @return array<string, mixed> */
    public function detail(
        AuthorizedFamilyContext $context,
        CatalogResourceType $resourceType,
        int $id,
    ): array {
        return match ($resourceType) {
            CatalogResourceType::Stores => $this->serializeStore(
                Store::query()->whereBelongsTo($context->family)->with('storeSections')->findOrFail($id),
            ),
            CatalogResourceType::StoreSections => $this->serializeStoreSection(
                StoreSection::query()->whereBelongsTo($context->family)->with('stores')->findOrFail($id),
            ),
            CatalogResourceType::Ingredients => $this->serializeIngredient(
                $ingredient = Ingredient::query()
                    ->whereBelongsTo($context->family)
                    ->with('nutritionProfile')
                    ->findOrFail($id),
                $this->alternativeIds($context, [$ingredient->id]),
            ),
            CatalogResourceType::RecipeTags => $this->serializeRecipeTag(
                RecipeTag::query()->whereBelongsTo($context->family)->with('recipes:id')->findOrFail($id),
            ),
            CatalogResourceType::Recipes => $this->serializeRecipe(
                Recipe::query()->whereBelongsTo($context->family)->with(['ingredients', 'steps', 'tags:id'])->findOrFail($id),
            ),
            CatalogResourceType::CalendarEntries => $this->serializeCalendarEntry(
                CalendarEntry::query()->whereBelongsTo($context->family)->findOrFail($id),
            ),
        };
    }

    /** @return list<array<string, mixed>> */
    private function resources(AuthorizedFamilyContext $context, CatalogResourceType $resourceType): array
    {
        $resources = match ($resourceType) {
            CatalogResourceType::Stores => Store::query()
                ->whereBelongsTo($context->family)
                ->with('storeSections')
                ->orderBy('id')
                ->get()
                ->map(fn (Store $store): array => $this->serializeStore($store))
                ->values()
                ->all(),
            CatalogResourceType::StoreSections => StoreSection::query()
                ->whereBelongsTo($context->family)
                ->with('stores')
                ->orderBy('id')
                ->get()
                ->map(fn (StoreSection $storeSection): array => $this->serializeStoreSection($storeSection))
                ->values()
                ->all(),
            CatalogResourceType::Ingredients => $this->ingredients($context),
            CatalogResourceType::RecipeTags => RecipeTag::query()
                ->whereBelongsTo($context->family)
                ->with('recipes:id')
                ->orderBy('id')
                ->get()
                ->map(fn (RecipeTag $recipeTag): array => $this->serializeRecipeTag($recipeTag))
                ->values()
                ->all(),
            CatalogResourceType::Recipes => Recipe::query()
                ->whereBelongsTo($context->family)
                ->with(['ingredients', 'steps', 'tags:id'])
                ->orderBy('id')
                ->get()
                ->map(fn (Recipe $recipe): array => $this->serializeRecipe($recipe))
                ->values()
                ->all(),
            CatalogResourceType::CalendarEntries => CalendarEntry::query()
                ->whereBelongsTo($context->family)
                ->orderBy('id')
                ->get()
                ->map(fn (CalendarEntry $calendarEntry): array => $this->serializeCalendarEntry($calendarEntry))
                ->values()
                ->all(),
        };

        return array_values($resources);
    }

    /** @return list<array<string, mixed>> */
    private function ingredients(AuthorizedFamilyContext $context): array
    {
        $ingredients = Ingredient::query()
            ->whereBelongsTo($context->family)
            ->with('nutritionProfile')
            ->orderBy('id')
            ->get();
        $ingredientIds = array_values($ingredients
            ->map(fn (Ingredient $ingredient): int => $ingredient->id)
            ->all());
        $alternativeIds = $this->alternativeIds($context, $ingredientIds);

        return array_values($ingredients
            ->map(fn (Ingredient $ingredient): array => $this->serializeIngredient($ingredient, $alternativeIds))
            ->all());
    }

    /** @return array<string, mixed> */
    private function serializeStore(Store $store): array
    {
        return [
            ...$this->identity(CatalogResourceType::Stores, $store->id, 'active', $store->updated_at),
            'name' => $store->name,
            'normalized_name' => $store->normalized_name,
            'section_order_version' => $store->section_order_version,
            'store_sections' => $store->storeSections->map(function (StoreSection $section): array {
                $pivot = $section->getRelation('pivot');

                if ( ! $pivot instanceof Pivot || ! is_int($pivot->getAttribute('position'))) {
                    throw new LogicException('Store Section order metadata is unavailable.');
                }

                return [
                    'store_section_id' => $section->id,
                    'position' => $pivot->getAttribute('position'),
                ];
            })->values()->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function serializeStoreSection(StoreSection $storeSection): array
    {
        return [
            ...$this->identity(CatalogResourceType::StoreSections, $storeSection->id, 'active', $storeSection->updated_at),
            'name' => $storeSection->name,
            'normalized_name' => $storeSection->normalized_name,
            'colour' => $storeSection->colour,
            'icon' => $storeSection->icon->value,
            'store_ids' => $storeSection->stores->pluck('id')->sort()->values()->all(),
        ];
    }

    /**
     * @param  array<int, list<int>>  $alternativeIds
     * @return array<string, mixed>
     */
    private function serializeIngredient(Ingredient $ingredient, array $alternativeIds): array
    {
        $nutritionProfile = $ingredient->nutritionProfile;

        return [
            ...$this->identity(
                CatalogResourceType::Ingredients,
                $ingredient->id,
                $ingredient->archived_at === null ? 'active' : 'archived',
                $ingredient->updated_at,
            ),
            'name' => $ingredient->name,
            'normalized_name' => $ingredient->normalized_name,
            'description' => $ingredient->description,
            'package_quantities' => [
                'weight_grams' => $ingredient->weight_grams,
                'volume_millilitres' => $ingredient->volume_millilitres,
                'piece_count' => $ingredient->piece_count,
            ],
            'nutrition_profile' => $nutritionProfile instanceof IngredientNutritionProfile ? [
                'basis_kind' => $nutritionProfile->basis_kind,
                'basis_quantity' => $nutritionProfile->basis_quantity,
                'energy_kcal' => $nutritionProfile->energy_kcal,
                'fat_grams' => $nutritionProfile->fat_grams,
                'protein_grams' => $nutritionProfile->protein_grams,
                'carbohydrate_grams' => $nutritionProfile->carbohydrate_grams,
            ] : null,
            'store_placement' => [
                'store_id' => $ingredient->store_id,
                'store_section_id' => $ingredient->store_section_id,
            ],
            'alternative_ingredient_ids' => $alternativeIds[$ingredient->id] ?? [],
        ];
    }

    /** @return array<string, mixed> */
    private function serializeRecipeTag(RecipeTag $recipeTag): array
    {
        return [
            ...$this->identity(CatalogResourceType::RecipeTags, $recipeTag->id, 'active', $recipeTag->updated_at),
            'name' => $recipeTag->name,
            'normalized_name' => $recipeTag->normalized_name,
            'recipe_ids' => $recipeTag->recipes->pluck('id')->sort()->values()->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function serializeRecipe(Recipe $recipe): array
    {
        $nutritionOverride = $recipe->nutrition_energy_kcal === null ? null : [
            'energy_kcal' => $recipe->nutrition_energy_kcal,
            'fat_grams' => $recipe->nutrition_fat_grams,
            'protein_grams' => $recipe->nutrition_protein_grams,
            'carbohydrate_grams' => $recipe->nutrition_carbohydrate_grams,
        ];

        return [
            ...$this->identity(
                CatalogResourceType::Recipes,
                $recipe->id,
                $recipe->archived_at === null ? 'active' : 'archived',
                $recipe->updated_at,
            ),
            'name' => $recipe->name,
            'normalized_name' => $recipe->normalized_name,
            'base_servings' => $recipe->base_servings,
            'source_url' => $recipe->source_url,
            'preparation_minutes' => $recipe->preparation_minutes,
            'cooking_minutes' => $recipe->cooking_minutes,
            'notes' => $recipe->notes,
            'version' => $recipe->version,
            'ingredients' => $recipe->ingredients->map(fn (RecipeIngredient $line): array => [
                'ingredient_id' => $line->ingredient_id,
                'position' => $line->position,
                'quantity' => $line->quantity,
                'quantity_kind' => $line->quantity_kind,
            ])->values()->all(),
            'steps' => $recipe->steps->map(fn (RecipeStep $step): array => [
                'position' => $step->position,
                'instruction' => $step->instruction,
            ])->values()->all(),
            'recipe_tag_ids' => $recipe->tags->pluck('id')->sort()->values()->all(),
            'nutrition_override' => $nutritionOverride,
        ];
    }

    /** @return array<string, mixed> */
    private function serializeCalendarEntry(CalendarEntry $calendarEntry): array
    {
        return [
            ...$this->identity(CatalogResourceType::CalendarEntries, $calendarEntry->id, 'active', $calendarEntry->updated_at),
            'recipe_id' => $calendarEntry->recipe_id,
            'date' => $calendarEntry->date->toDateString(),
            'meal_label' => MealLabel::nullableFromKey($calendarEntry->meal_label_key)?->value,
            'serving_count' => $calendarEntry->serving_count,
        ];
    }

    /**
     * @param  list<int>  $ingredientIds
     * @return array<int, list<int>>
     */
    private function alternativeIds(AuthorizedFamilyContext $context, array $ingredientIds): array
    {
        $alternatives = array_fill_keys($ingredientIds, []);
        $rows = DB::table('ingredient_alternatives')
            ->where('family_id', $context->family->id)
            ->where(function (Builder $query) use ($ingredientIds): void {
                $query->whereIn('lower_ingredient_id', $ingredientIds)
                    ->orWhereIn('higher_ingredient_id', $ingredientIds);
            })
            ->get(['lower_ingredient_id', 'higher_ingredient_id']);

        foreach ($rows as $row) {
            $lowerId = $row->lower_ingredient_id;
            $higherId = $row->higher_ingredient_id;

            if ( ! is_int($lowerId) || ! is_int($higherId)) {
                throw new LogicException('Ingredient Alternative identifiers must be integers.');
            }

            if (array_key_exists($lowerId, $alternatives)) {
                $alternatives[$lowerId][] = $higherId;
            }

            if (array_key_exists($higherId, $alternatives)) {
                $alternatives[$higherId][] = $lowerId;
            }
        }

        foreach ($alternatives as &$ids) {
            sort($ids);
        }

        return $alternatives;
    }

    /** @return array{resource_type: string, id: int, status: string, updated_at: string} */
    private function identity(
        CatalogResourceType $resourceType,
        int $id,
        string $status,
        ?CarbonInterface $updatedAt,
    ): array {
        if ( ! $updatedAt instanceof CarbonInterface) {
            throw new LogicException('Catalog resources require an updated_at timestamp.');
        }

        return [
            'resource_type' => $resourceType->value,
            'id' => $id,
            'status' => $status,
            'updated_at' => $updatedAt->utc()->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
