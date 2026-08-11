<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Controllers;

use App\Cookbook\Actions\AttachIngredientAlternative;
use App\Cookbook\Actions\DetachIngredientAlternative;
use App\Cookbook\Http\Requests\IngredientAlternativeDestroyRequest;
use App\Cookbook\Http\Requests\IngredientAlternativeStoreRequest;
use App\FamilyAccess\AuthorizedFamilyContext;
use App\FamilyAccess\CurrentFamilyScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class IngredientAlternativeController extends Controller
{
    public function __construct(
        private readonly CurrentFamilyScope $currentFamilyScope,
        private readonly AttachIngredientAlternative $attachIngredientAlternative,
        private readonly DetachIngredientAlternative $detachIngredientAlternative,
    ) {}

    public function store(IngredientAlternativeStoreRequest $request, int $ingredient): RedirectResponse
    {
        $this->currentFamilyScope->withinContext(
            $request->authenticatedUser(),
            function (AuthorizedFamilyContext $context) use ($ingredient, $request): void {
                $this->attachIngredientAlternative->handle($context, $ingredient, $request->alternativeId());
            },
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Alternative Ingredient linked.')]);

        return to_route('ingredients.index');
    }

    public function destroy(IngredientAlternativeDestroyRequest $request, int $ingredient, int $alternative): RedirectResponse
    {
        $this->currentFamilyScope->withinContext(
            $request->authenticatedUser(),
            function (AuthorizedFamilyContext $context) use ($alternative, $ingredient): void {
                $this->detachIngredientAlternative->handle($context, $ingredient, $alternative);
            },
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Alternative Ingredient link removed.')]);

        return to_route('ingredients.index');
    }
}
