<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Requests;

use App\Cookbook\Values\NormalizedName;
use App\Http\Requests\AuthenticatedRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Pagination\Cursor;
use Illuminate\Validation\Rule;

final class RelationSearchRequest extends AuthenticatedRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'cursor' => ['nullable', 'string', 'max:2048'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'store_id' => [
                Rule::requiredIf($this->routeIs('relation-search.store-sections')),
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }

    public function searchTerm(): string
    {
        $search = $this->validated('q');

        return is_string($search) ? NormalizedName::from($search)->key : '';
    }

    public function resultLimit(): int
    {
        $limit = $this->validated('limit');
        $default = config('relation_search.default_limit', 20);

        return is_numeric($limit) ? (int) $limit : (is_int($default) ? $default : 20);
    }

    public function searchCursor(): ?Cursor
    {
        $cursor = $this->validated('cursor');

        return is_string($cursor) ? Cursor::fromEncoded($cursor) : null;
    }

    public function storeId(): int
    {
        $storeId = $this->validated('store_id');

        return is_numeric($storeId) ? (int) $storeId : 0;
    }

    protected function prepareForValidation(): void
    {
        $search = $this->input('q');
        if (is_string($search)) {
            $display = NormalizedName::from($search)->display;
            $this->merge(['q' => $display === '' ? null : $display]);
        }
    }
}
