<?php

declare(strict_types=1);

namespace App\Cookbook\Queries;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Services\EntityMediaStorage;
use App\Cookbook\Values\EntityMediaType;
use App\Cookbook\Values\NormalizedName;
use App\FamilyAccess\Models\Family;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class CurrentFamilyIngredientManagement
{
    public function __construct(private EntityMediaStorage $entityMediaStorage) {}

    /** @return array<string, mixed> */
    public function handle(Family $family, string $filter): array
    {
        $catalog = $this->catalog($family);
        $catalogById = $catalog->keyBy(fn (Ingredient $ingredient): int => $ingredient->id);
        $alternativeIds = $this->alternativeIds($family);

        return [
            'ingredients' => $catalog
                ->filter(fn (Ingredient $ingredient): bool => $this->matchesFilter($ingredient, $filter))
                ->map(fn (Ingredient $ingredient): array => $this->ingredient(
                    $family,
                    $ingredient,
                    $catalogById,
                    $alternativeIds[$ingredient->id] ?? [],
                ))
                ->values()
                ->all(),
            'alternativeOptions' => $catalog
                ->filter(fn (Ingredient $ingredient): bool => $ingredient->archived_at === null)
                ->map(fn (Ingredient $ingredient): array => ['id' => $ingredient->id, 'name' => $ingredient->name])
                ->values()
                ->all(),
            'filter' => $filter,
        ];
    }

    /** @return Collection<int, Ingredient> */
    private function catalog(Family $family): Collection
    {
        return Ingredient::query()
            ->whereBelongsTo($family)
            ->select(['id', 'name', 'normalized_name', 'description', 'weight_grams', 'volume_millilitres', 'piece_count', 'store_id', 'store_section_id', 'archived_at'])
            ->with(['store:id,name', 'storeSection:id,name,colour,icon', 'nutritionProfile'])
            ->get()
            ->sort(fn (Ingredient $left, Ingredient $right): int => NormalizedName::compare(
                $left->normalized_name,
                $left->id,
                $right->normalized_name,
                $right->id,
            ))
            ->values();
    }

    /** @return array<int, array<int, true>> */
    private function alternativeIds(Family $family): array
    {
        $alternativeIds = [];
        $edges = DB::table('ingredient_alternatives')
            ->where('family_id', $family->id)
            ->select(['lower_ingredient_id', 'higher_ingredient_id'])
            ->get();

        foreach ($edges as $edge) {
            $lowerIngredientId = $edge->lower_ingredient_id;
            $higherIngredientId = $edge->higher_ingredient_id;

            if (( ! is_int($lowerIngredientId) && ! is_string($lowerIngredientId))
                || ( ! is_int($higherIngredientId) && ! is_string($higherIngredientId))) {
                continue;
            }

            $lowerId = (int) $lowerIngredientId;
            $higherId = (int) $higherIngredientId;
            $alternativeIds[$lowerId][$higherId] = true;
            $alternativeIds[$higherId][$lowerId] = true;
        }

        return $alternativeIds;
    }

    /**
     * @param  Collection<int, Ingredient>  $catalogById
     * @param  array<int, true>  $alternativeIds
     * @return array<string, mixed>
     */
    private function ingredient(Family $family, Ingredient $ingredient, Collection $catalogById, array $alternativeIds): array
    {
        $alternatives = collect(array_keys($alternativeIds))
            ->map(fn (int $alternativeId): ?Ingredient => $catalogById->get($alternativeId))
            ->filter(fn (?Ingredient $alternative): bool => $alternative !== null)
            ->map(fn (Ingredient $alternative): array => [
                'id' => $alternative->id,
                'name' => $alternative->name,
                'archived' => $alternative->archived_at !== null,
            ])
            ->values()
            ->all();

        return [
            'id' => $ingredient->id,
            'name' => $ingredient->name,
            'photoUrl' => $this->entityMediaStorage->url($family, EntityMediaType::IngredientPhoto, $ingredient->id),
            'description' => $ingredient->description,
            'metricQuantity' => $ingredient->weight_grams ?? $ingredient->volume_millilitres,
            'metricUnit' => $ingredient->volume_millilitres === null ? 'g' : 'ml',
            'pieceCount' => $ingredient->piece_count,
            'quantities' => $ingredient->packageQuantities()->display(),
            'storeId' => $ingredient->store_id,
            'storeSectionId' => $ingredient->store_section_id,
            'store' => $ingredient->store === null ? null : [
                'id' => $ingredient->store->id,
                'name' => $ingredient->store->name,
                'logoUrl' => $this->entityMediaStorage->url(
                    $family,
                    EntityMediaType::StoreLogo,
                    $ingredient->store->id,
                ),
            ],
            'storeSection' => $ingredient->storeSection === null ? null : [
                'id' => $ingredient->storeSection->id,
                'name' => $ingredient->storeSection->name,
                'colour' => $ingredient->storeSection->colour,
                'icon' => $ingredient->storeSection->icon->value,
                'iconUrl' => $this->entityMediaStorage->url(
                    $family,
                    EntityMediaType::StoreSectionIcon,
                    $ingredient->storeSection->id,
                ),
            ],
            'placement' => $ingredient->store === null
                ? null
                : implode(' · ', array_filter([$ingredient->store->name, $ingredient->storeSection?->name])),
            'archived' => $ingredient->archived_at !== null,
            'alternatives' => $alternatives,
            'nutrition' => $ingredient->nutritionProfile === null ? null : [
                'basisKind' => $ingredient->nutritionProfile->basis_kind,
                'basisQuantity' => $ingredient->nutritionProfile->basis_quantity,
                'energyKcal' => $ingredient->nutritionProfile->energy_kcal,
                'fatGrams' => $ingredient->nutritionProfile->fat_grams,
                'proteinGrams' => $ingredient->nutritionProfile->protein_grams,
                'carbohydrateGrams' => $ingredient->nutritionProfile->carbohydrate_grams,
            ],
        ];
    }

    private function matchesFilter(Ingredient $ingredient, string $filter): bool
    {
        return match ($filter) {
            'active' => $ingredient->archived_at === null,
            'archived' => $ingredient->archived_at !== null,
            default => true,
        };
    }
}
