<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Requests;

use App\Http\Requests\AuthenticatedRequest;

final class RecipeMutationRequest extends AuthenticatedRequest
{
    /** @return array<string, never> */
    public function rules(): array
    {
        return [];
    }
}
