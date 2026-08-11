<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Requests;

use App\Http\Requests\AuthenticatedRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

final class IngredientIndexRequest extends AuthenticatedRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'filter' => ['sometimes', 'string', Rule::in(['active', 'archived', 'all'])],
            'edit' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function ingredientFilter(): string
    {
        $filter = $this->validated('filter', 'active');

        return is_string($filter) ? $filter : 'active';
    }

    public function editIngredientId(): ?int
    {
        $ingredientId = $this->validated('edit');

        return is_numeric($ingredientId) ? (int) $ingredientId : null;
    }
}
