<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Requests;

use App\Cookbook\Values\NormalizedName;
use App\Http\Requests\AuthenticatedRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

final class RecipeIndexRequest extends AuthenticatedRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return ['filter' => ['nullable', Rule::in(['active', 'archived', 'all'])], 'search' => ['nullable', 'string', 'max:255']];
    }

    public function recipeFilter(): string
    {
        $filter = $this->validated('filter');

        return is_string($filter) ? $filter : 'active';
    }

    public function search(): string
    {
        $search = $this->validated('search');

        return is_string($search) ? trim($search) : '';
    }

    protected function prepareForValidation(): void
    {
        $search = $this->input('search');
        if (is_string($search)) {
            $display = NormalizedName::from($search)->display;
            $this->merge(['search' => $display === '' ? null : $display]);
        }
    }
}
