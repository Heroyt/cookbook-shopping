<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Controllers;

use App\Cookbook\Actions\ArchiveIngredient;
use App\Cookbook\Actions\CreateIngredient;
use App\Cookbook\Actions\RestoreIngredient;
use App\Cookbook\Actions\UpdateIngredient;
use App\Cookbook\Http\Requests\IngredientArchiveRequest;
use App\Cookbook\Http\Requests\IngredientIndexRequest;
use App\Cookbook\Http\Requests\IngredientRestoreRequest;
use App\Cookbook\Http\Requests\IngredientStoreRequest;
use App\Cookbook\Http\Requests\IngredientUpdateRequest;
use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\Store;
use App\Cookbook\Models\StoreSection;
use App\Cookbook\Values\NormalizedName;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class IngredientController extends Controller
{
    public function __construct(
        private readonly CurrentFamilyScope $currentFamilyScope,
        private readonly CreateIngredient $createIngredient,
        private readonly UpdateIngredient $updateIngredient,
        private readonly ArchiveIngredient $archiveIngredient,
        private readonly RestoreIngredient $restoreIngredient,
    ) {}

    public function index(IngredientIndexRequest $request): Response
    {
        $filter = $request->ingredientFilter();
        $managementData = $this->currentFamilyScope->within(
            $request->authenticatedUser(),
            function (Family $family) use ($filter): array {
                $catalog = Ingredient::query()
                    ->whereBelongsTo($family)
                    ->select(['id', 'name', 'normalized_name', 'description', 'weight_grams', 'volume_millilitres', 'piece_count', 'store_id', 'store_section_id', 'archived_at'])
                    ->with(['store:id,name', 'storeSection:id,name', 'nutritionProfile'])
                    ->get()
                    ->sort(fn (Ingredient $left, Ingredient $right): int => NormalizedName::compare(
                        $left->normalized_name,
                        $left->id,
                        $right->normalized_name,
                        $right->id,
                    ))
                    ->values();
                $alternativeIds = [];

                foreach (DB::table('ingredient_alternatives')->where('family_id', $family->id)->get() as $edge) {
                    $lowerIngredientId = $edge->lower_ingredient_id;
                    $higherIngredientId = $edge->higher_ingredient_id;

                    if (( ! is_int($lowerIngredientId) && ! is_string($lowerIngredientId))
                        || ( ! is_int($higherIngredientId) && ! is_string($higherIngredientId))) {
                        continue;
                    }

                    $lowerId = (int) $lowerIngredientId;
                    $higherId = (int) $higherIngredientId;
                    $alternativeIds[$lowerId][] = $higherId;
                    $alternativeIds[$higherId][] = $lowerId;
                }

                $visibleIngredients = $catalog->filter(fn (Ingredient $ingredient): bool => match ($filter) {
                    'active' => $ingredient->archived_at === null,
                    'archived' => $ingredient->archived_at !== null,
                    default => true,
                });

                return [
                    'ingredients' => $visibleIngredients
                        ->map(fn (Ingredient $ingredient): array => [
                            'id' => $ingredient->id,
                            'name' => $ingredient->name,
                            'description' => $ingredient->description,
                            'metricQuantity' => $ingredient->weight_grams ?? $ingredient->volume_millilitres,
                            'metricUnit' => $ingredient->volume_millilitres === null ? 'g' : 'ml',
                            'pieceCount' => $ingredient->piece_count,
                            'quantities' => $ingredient->packageQuantities()->display(),
                            'storeId' => $ingredient->store_id,
                            'storeSectionId' => $ingredient->store_section_id,
                            'placement' => $ingredient->store === null
                                ? null
                                : implode(' · ', array_filter([$ingredient->store->name, $ingredient->storeSection?->name])),
                            'archived' => $ingredient->archived_at !== null,
                            'alternatives' => $catalog
                                ->filter(fn (Ingredient $candidate): bool => in_array(
                                    $candidate->id,
                                    $alternativeIds[$ingredient->id] ?? [],
                                    true,
                                ))
                                ->map(fn (Ingredient $candidate): array => [
                                    'id' => $candidate->id,
                                    'name' => $candidate->name,
                                    'archived' => $candidate->archived_at !== null,
                                ])
                                ->values()
                                ->all(),
                            'alternativeOptions' => $catalog
                                ->filter(fn (Ingredient $candidate): bool => $candidate->id !== $ingredient->id
                                    && $candidate->archived_at === null
                                    && ! in_array($candidate->id, $alternativeIds[$ingredient->id] ?? [], true))
                                ->map(fn (Ingredient $candidate): array => ['id' => $candidate->id, 'name' => $candidate->name])
                                ->values()
                                ->all(),
                            'nutrition' => $ingredient->nutritionProfile === null ? null : [
                                'basisKind' => $ingredient->nutritionProfile->basis_kind,
                                'basisQuantity' => $ingredient->nutritionProfile->basis_quantity,
                                'energyKcal' => $ingredient->nutritionProfile->energy_kcal,
                                'fatGrams' => $ingredient->nutritionProfile->fat_grams,
                                'proteinGrams' => $ingredient->nutritionProfile->protein_grams,
                                'carbohydrateGrams' => $ingredient->nutritionProfile->carbohydrate_grams,
                            ],
                        ])
                        ->all(),
                    'filter' => $filter,
                    'stores' => Store::query()
                        ->whereBelongsTo($family)
                        ->select(['id', 'name', 'normalized_name'])
                        ->with('storeSections:id,name,colour')
                        ->get()
                        ->sort(fn (Store $left, Store $right): int => NormalizedName::compare(
                            $left->normalized_name,
                            $left->id,
                            $right->normalized_name,
                            $right->id,
                        ))
                        ->values()
                        ->map(fn (Store $store): array => [
                            'id' => $store->id,
                            'name' => $store->name,
                            'sections' => $store->storeSections->map(fn (StoreSection $storeSection): array => [
                                'id' => $storeSection->id,
                                'name' => $storeSection->name,
                                'colour' => $storeSection->colour,
                            ])->all(),
                        ])
                        ->all(),
                ];
            },
        );

        return Inertia::render('ingredients/Index', $managementData);
    }

    public function archive(IngredientArchiveRequest $request, int $ingredient): RedirectResponse
    {
        $this->archiveIngredient->handle($request->authenticatedUser(), $ingredient);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Ingredient archived.')]);

        return to_route('ingredients.index');
    }

    public function restore(IngredientRestoreRequest $request, int $ingredient): RedirectResponse
    {
        $this->restoreIngredient->handle($request->authenticatedUser(), $ingredient);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Ingredient restored.')]);

        return to_route('ingredients.index');
    }

    public function store(IngredientStoreRequest $request): RedirectResponse
    {
        $this->createIngredient->handle(
            $request->authenticatedUser(),
            $request->ingredientName(),
            $request->description(),
            $request->packageQuantities(),
            $request->storeId(),
            $request->storeSectionId(),
            $request->nutritionInput(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Ingredient created.')]);

        return to_route('ingredients.index');
    }

    public function update(IngredientUpdateRequest $request): RedirectResponse
    {
        $this->updateIngredient->handle(
            $request->authenticatedUser(),
            $request->ingredientId(),
            $request->ingredientName(),
            $request->description(),
            $request->packageQuantities(),
            $request->storeId(),
            $request->storeSectionId(),
            $request->nutritionInput(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Ingredient updated.')]);

        return to_route('ingredients.index');
    }
}
