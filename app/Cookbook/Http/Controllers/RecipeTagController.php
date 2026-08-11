<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Controllers;

use App\Cookbook\Actions\CreateRecipeTag;
use App\Cookbook\Actions\DeleteRecipeTag;
use App\Cookbook\Http\Requests\RecipeMutationRequest;
use App\Cookbook\Http\Requests\RecipeTagStoreRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class RecipeTagController extends Controller
{
    public function __construct(private readonly CreateRecipeTag $createTag, private readonly DeleteRecipeTag $deleteTag) {}

    public function store(RecipeTagStoreRequest $request): RedirectResponse
    {
        $this->createTag->handle($request->authenticatedUser(), $request->tagName());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Recipe Tag created.')]);

        return to_route('recipes.index');
    }

    public function destroy(RecipeMutationRequest $request, int $recipeTag): RedirectResponse
    {
        $this->deleteTag->handle($request->authenticatedUser(), $recipeTag);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Recipe Tag deleted.')]);

        return to_route('recipes.index');
    }
}
