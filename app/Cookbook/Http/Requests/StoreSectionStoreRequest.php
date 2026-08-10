<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Requests;

use App\Cookbook\Values\NormalizedName;
use App\Http\Requests\AuthenticatedRequest;
use Illuminate\Contracts\Validation\ValidationRule;

final class StoreSectionStoreRequest extends AuthenticatedRequest
{
    public function storeSectionName(): string
    {
        return $this->string('name')->toString();
    }

    public function storeSectionColour(): string
    {
        return $this->string('colour')->toString();
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'colour' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'colour.regex' => __('The colour must be a six-digit hexadecimal value.'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => NormalizedName::from($this->string('name')->toString())->display,
        ]);
    }
}
