<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Requests;

use App\Http\Requests\AuthenticatedRequest;
use Illuminate\Contracts\Validation\ValidationRule;

final class StoreSectionColourUpdateRequest extends AuthenticatedRequest
{
    public function storeSectionColour(): string
    {
        return $this->string('colour')->toString();
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
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
}
