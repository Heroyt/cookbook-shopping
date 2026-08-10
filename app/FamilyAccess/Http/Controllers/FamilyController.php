<?php

declare(strict_types=1);

namespace App\FamilyAccess\Http\Controllers;

use App\FamilyAccess\Actions\CreateFamily;
use App\FamilyAccess\Actions\DeleteFamily;
use App\FamilyAccess\CurrentFamily;
use App\FamilyAccess\Http\Requests\DeleteFamilyRequest;
use App\FamilyAccess\Http\Requests\FamilyIndexRequest;
use App\FamilyAccess\Http\Requests\StoreFamilyRequest;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class FamilyController extends Controller
{
    public function __construct(
        private readonly CreateFamily $createFamily,
        private readonly DeleteFamily $deleteFamily,
        private readonly CurrentFamily $currentFamily,
    ) {}

    public function index(FamilyIndexRequest $request): Response
    {
        $family = $this->currentFamily->resolve($request->authenticatedUser());
        $members = $family?->members()
            ->select(['users.id', 'users.name', 'users.email'])
            ->orderBy('users.id')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);

        return Inertia::render('families/Index', [
            'family' => $family === null ? null : [
                'id' => $family->id,
                'name' => $family->name,
                'members' => $members,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('families/Create');
    }

    public function store(StoreFamilyRequest $request): RedirectResponse
    {
        $this->createFamily->handle($request->authenticatedUser(), $request->familyName());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Family created.')]);

        return to_route('families.index');
    }

    public function destroy(DeleteFamilyRequest $request): RedirectResponse
    {
        $family = $this->currentFamily->resolve($request->authenticatedUser()) ?? abort(404);

        $this->deleteFamily->handle(
            $request->authenticatedUser(),
            $family,
            $request->confirmedFamilyName(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Family deleted.')]);

        return to_route('families.index');
    }
}
