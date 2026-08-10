<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Requests;

use App\Http\Requests\AuthenticatedRequest;
use Illuminate\Contracts\Validation\ValidationRule;

final class StoreSectionDestroyRequest extends AuthenticatedRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
