<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Controllers;

use App\Cookbook\Actions\ArchiveRecipe;
use App\Cookbook\Actions\CreateRecipe;
use App\Cookbook\Actions\RestoreRecipe;
use App\Cookbook\Actions\UpdateRecipe;
use App\Cookbook\Http\Requests\RecipeIndexRequest;
use App\Cookbook\Http\Requests\RecipeMutationRequest;
use App\Cookbook\Http\Requests\RecipeStoreRequest;
use App\Cookbook\Http\Requests\RecipeUpdateRequest;
use App\Cookbook\Queries\CurrentFamilyRecipeManagement;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class RecipeController extends Controller
{
    public function __construct(
        private readonly CurrentFamilyScope $scope,
        private readonly CreateRecipe $createRecipe,
        private readonly UpdateRecipe $updateRecipe,
        private readonly ArchiveRecipe $archiveRecipe,
        private readonly RestoreRecipe $restoreRecipe,
        private readonly CurrentFamilyRecipeManagement $management,
    ) {}

    public function index(RecipeIndexRequest $request): Response
    {
        $data = $this->scope->within($request->authenticatedUser(), fn (Family $family): array => $this->management->handle($family, $request->recipeFilter(), $request->search()));

        return Inertia::render('recipes/Index', $data);
    }

    public function store(RecipeStoreRequest $request): RedirectResponse
    {
        $this->createRecipe->handle($request->authenticatedUser(), $request->recipeData());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Recipe created.')]);

        return to_route('recipes.index');
    }

    public function update(RecipeUpdateRequest $request, int $recipe): RedirectResponse
    {
        $this->updateRecipe->handle($request->authenticatedUser(), $recipe, $request->recipeVersion(), $request->recipeData());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Recipe saved.')]);

        return to_route('recipes.index');
    }

    public function archive(RecipeMutationRequest $request, int $recipe): RedirectResponse
    {
        $this->archiveRecipe->handle($request->authenticatedUser(), $recipe);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Recipe archived.')]);

        return to_route('recipes.index');
    }

    public function restore(RecipeMutationRequest $request, int $recipe): RedirectResponse
    {
        $this->restoreRecipe->handle($request->authenticatedUser(), $recipe);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Recipe restored.')]);

        return to_route('recipes.index');
    }
}
