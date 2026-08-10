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
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
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
            fn (Family $family): array => [
                'ingredients' => Ingredient::query()
                    ->whereBelongsTo($family)
                    ->when($filter === 'active', fn ($query) => $query->whereNull('archived_at'))
                    ->when($filter === 'archived', fn ($query) => $query->whereNotNull('archived_at'))
                    ->select(['id', 'name', 'description', 'weight_grams', 'volume_millilitres', 'piece_count', 'store_id', 'store_section_id', 'archived_at'])
                    ->with(['store:id,name', 'storeSection:id,name'])
                    ->orderBy('name')
                    ->get()
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
                    ])
                    ->all(),
                'filter' => $filter,
                'stores' => Store::query()
                    ->whereBelongsTo($family)
                    ->select(['id', 'name'])
                    ->with('storeSections:id,name,colour')
                    ->orderBy('name')
                    ->get()
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
            ],
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
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Ingredient updated.')]);

        return to_route('ingredients.index');
    }
}
