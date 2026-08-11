<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Http\Requests;

use App\Http\Requests\AuthenticatedRequest;

final class SavedShoppingListRequest extends AuthenticatedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
