<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Controllers;

use App\Cookbook\Actions\CreateStoreSection;
use App\Cookbook\Http\Requests\StoreSectionStoreRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class StoreSectionController extends Controller
{
    public function __construct(private readonly CreateStoreSection $createStoreSection) {}

    public function store(StoreSectionStoreRequest $request): RedirectResponse
    {
        $this->createStoreSection->handle(
            $request->authenticatedUser(),
            $request->storeSectionName(),
            $request->storeSectionColour(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Store Section created.')]);

        return to_route('stores.index');
    }
}
