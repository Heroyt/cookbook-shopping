<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Requests;

use App\Cookbook\Values\NormalizedName;
use App\Http\Requests\AuthenticatedRequest;
use Illuminate\Contracts\Validation\ValidationRule;

final class StoreUpdateRequest extends AuthenticatedRequest
{
    public function storeName(): string
    {
        return $this->string('name')->toString();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => NormalizedName::from($this->string('name')->toString())->display,
        ]);
    }
}
