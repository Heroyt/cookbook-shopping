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
use App\Cookbook\Queries\CurrentFamilyIngredientManagement;
use App\FamilyAccess\AuthorizedFamilyContext;
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
        private readonly CurrentFamilyIngredientManagement $currentFamilyIngredientManagement,
    ) {}

    public function index(IngredientIndexRequest $request): Response
    {
        $filter = $request->ingredientFilter();
        $managementData = $this->currentFamilyScope->within(
            $request->authenticatedUser(),
            fn (Family $family): array => $this->currentFamilyIngredientManagement->handle($family, $filter),
        );

        return Inertia::render('ingredients/Index', [
            ...$managementData,
            'editIngredientId' => $request->editIngredientId(),
        ]);
    }

    public function archive(IngredientArchiveRequest $request, int $ingredient): RedirectResponse
    {
        $this->currentFamilyScope->withinContext(
            $request->authenticatedUser(),
            function (AuthorizedFamilyContext $context) use ($ingredient): void {
                $this->archiveIngredient->handle($context, $ingredient);
            },
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Ingredient archived.')]);

        return to_route('ingredients.index');
    }

    public function restore(IngredientRestoreRequest $request, int $ingredient): RedirectResponse
    {
        $this->currentFamilyScope->withinContext(
            $request->authenticatedUser(),
            function (AuthorizedFamilyContext $context) use ($ingredient): void {
                $this->restoreIngredient->handle($context, $ingredient);
            },
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Ingredient restored.')]);

        return to_route('ingredients.index');
    }

    public function store(IngredientStoreRequest $request): RedirectResponse
    {
        $this->currentFamilyScope->withinContext(
            $request->authenticatedUser(),
            fn (AuthorizedFamilyContext $context): Ingredient => $this->createIngredient->handle(
                $context,
                $request->ingredientName(),
                $request->description(),
                $request->packageQuantities(),
                $request->storeId(),
                $request->storeSectionId(),
                $request->nutritionInput(),
            ),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Ingredient created.')]);

        return to_route('ingredients.index');
    }

    public function update(IngredientUpdateRequest $request): RedirectResponse
    {
        $this->currentFamilyScope->withinContext(
            $request->authenticatedUser(),
            fn (AuthorizedFamilyContext $context): Ingredient => $this->updateIngredient->handle(
                $context,
                $request->ingredientId(),
                $request->ingredientName(),
                $request->description(),
                $request->packageQuantities(),
                $request->storeId(),
                $request->storeSectionId(),
                $request->nutritionInput(),
            ),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Ingredient updated.')]);

        return to_route('ingredients.index');
    }
}
