<?php

declare(strict_types=1);

namespace App\AgentIntegration;

use App\AgentIntegration\Models\AgentCredential;
use App\FamilyAccess\AuthorizedFamilyContext;
use App\FamilyAccess\Models\Family;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

final readonly class AgentCredentialFamilyContext
{
    public function resolve(Request $request): AuthorizedFamilyContext
    {
        $credential = $this->credential($request);
        $user = $request->user();

        if ( ! $user instanceof User) {
            throw new AuthenticationException();
        }

        $family = $credential->family()->first();

        if ( ! $family instanceof Family) {
            throw new AuthenticationException();
        }

        return new AuthorizedFamilyContext($user, $family);
    }

    public function credential(Request $request): AgentCredential
    {
        $user = $request->user();

        if ( ! $user instanceof User) {
            throw new AuthenticationException();
        }

        $credential = $user->currentAccessToken();

        if ( ! $credential instanceof AgentCredential) {
            throw new AuthenticationException();
        }

        return $credential;
    }
}
