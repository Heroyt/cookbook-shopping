<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Controllers;

use App\Cookbook\Actions\CreateIngredient;
use App\Cookbook\Actions\UpdateIngredient;
use App\Cookbook\Http\Requests\IngredientIndexRequest;
use App\Cookbook\Http\Requests\IngredientStoreRequest;
use App\Cookbook\Http\Requests\IngredientUpdateRequest;
use App\Cookbook\Models\Ingredient;
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
    ) {}

    public function index(IngredientIndexRequest $request): Response
    {
        $ingredients = $this->currentFamilyScope->within(
            $request->authenticatedUser(),
            fn (Family $family): array => Ingredient::query()
                ->whereBelongsTo($family)
                ->select(['id', 'name', 'description', 'weight_grams', 'volume_millilitres', 'piece_count'])
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
                ])
                ->all(),
        );

        return Inertia::render('ingredients/Index', ['ingredients' => $ingredients]);
    }

    public function store(IngredientStoreRequest $request): RedirectResponse
    {
        $this->createIngredient->handle(
            $request->authenticatedUser(),
            $request->ingredientName(),
            $request->description(),
            $request->packageQuantities(),
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
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Ingredient updated.')]);

        return to_route('ingredients.index');
    }
}
