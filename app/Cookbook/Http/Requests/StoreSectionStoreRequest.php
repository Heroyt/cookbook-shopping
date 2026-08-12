<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

final class StoreSectionStoreRequest extends StoreSectionWriteRequest
{
    public function layered(): bool
    {
        return $this->boolean('layered');
    }

    public function storeId(): ?int
    {
        return $this->filled('store_id') ? $this->integer('store_id') : null;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'layered' => ['sometimes', 'boolean'],
            'store_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
