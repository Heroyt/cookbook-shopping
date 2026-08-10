<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Controllers;

use App\Cookbook\Actions\CreateStore;
use App\Cookbook\Actions\DeleteStore;
use App\Cookbook\Actions\RenameStore;
use App\Cookbook\Http\Requests\StoreDestroyRequest;
use App\Cookbook\Http\Requests\StoreIndexRequest;
use App\Cookbook\Http\Requests\StoreStoreRequest;
use App\Cookbook\Http\Requests\StoreUpdateRequest;
use App\Cookbook\Models\Store;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class StoreController extends Controller
{
    public function __construct(
        private readonly CurrentFamilyScope $currentFamilyScope,
        private readonly CreateStore $createStore,
        private readonly RenameStore $renameStore,
        private readonly DeleteStore $deleteStore,
    ) {}

    public function index(StoreIndexRequest $request): Response
    {
        $stores = $this->currentFamilyScope->within(
            $request->authenticatedUser(),
            fn (Family $family) => Store::query()
                ->whereBelongsTo($family)
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get()
                ->map(fn (Store $store): array => [
                    'id' => $store->id,
                    'name' => $store->name,
                ]),
        );

        return Inertia::render('stores/Index', ['stores' => $stores]);
    }

    public function store(StoreStoreRequest $request): RedirectResponse
    {
        $this->createStore->handle($request->authenticatedUser(), $request->storeName());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Store created.')]);

        return to_route('stores.index');
    }

    public function update(StoreUpdateRequest $request, int $store): RedirectResponse
    {
        $this->renameStore->handle($request->authenticatedUser(), $store, $request->storeName());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Store renamed.')]);

        return to_route('stores.index');
    }

    public function destroy(StoreDestroyRequest $request, int $store): RedirectResponse
    {
        $this->deleteStore->handle($request->authenticatedUser(), $store);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Store deleted.')]);

        return to_route('stores.index');
    }
}
