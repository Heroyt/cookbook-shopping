<?php

declare(strict_types=1);

namespace App\FamilyAccess\Http\Controllers;

use App\FamilyAccess\Actions\CreateFamily;
use App\FamilyAccess\Http\Requests\StoreFamilyRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class FamilyController extends Controller
{
    public function __construct(private readonly CreateFamily $createFamily) {}

    public function create(): Response
    {
        return Inertia::render('families/Create');
    }

    public function store(StoreFamilyRequest $request): RedirectResponse
    {
        $this->createFamily->handle($request->authenticatedUser(), $request->familyName());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Family created.')]);

        return to_route('dashboard');
    }
}
