<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Requests;

use App\Cookbook\Values\NormalizedName;
use App\Cookbook\Values\StoreSectionIcon;
use App\Http\Requests\AuthenticatedRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

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

    public function storeSectionIcon(): StoreSectionIcon
    {
        return StoreSectionIcon::tryFrom($this->string('icon')->toString())
            ?? StoreSectionIcon::Package;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'colour' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['sometimes', Rule::enum(StoreSectionIcon::class)],
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
        $name = $this->input('name');

        if ( ! is_string($name)) {
            return;
        }

        $this->merge([
            'name' => NormalizedName::from($name)->display,
        ]);
    }
}
