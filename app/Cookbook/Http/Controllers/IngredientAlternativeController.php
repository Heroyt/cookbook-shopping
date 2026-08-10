<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Controllers;

use App\Cookbook\Actions\AttachIngredientAlternative;
use App\Cookbook\Actions\DetachIngredientAlternative;
use App\Cookbook\Http\Requests\IngredientAlternativeDestroyRequest;
use App\Cookbook\Http\Requests\IngredientAlternativeStoreRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class IngredientAlternativeController extends Controller
{
    public function __construct(
        private readonly AttachIngredientAlternative $attachIngredientAlternative,
        private readonly DetachIngredientAlternative $detachIngredientAlternative,
    ) {}

    public function store(IngredientAlternativeStoreRequest $request, int $ingredient): RedirectResponse
    {
        $this->attachIngredientAlternative->handle($request->authenticatedUser(), $ingredient, $request->alternativeId());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Alternative Ingredient linked.')]);

        return to_route('ingredients.index');
    }

    public function destroy(IngredientAlternativeDestroyRequest $request, int $ingredient, int $alternative): RedirectResponse
    {
        $this->detachIngredientAlternative->handle($request->authenticatedUser(), $ingredient, $alternative);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Alternative Ingredient link removed.')]);

        return to_route('ingredients.index');
    }
}
