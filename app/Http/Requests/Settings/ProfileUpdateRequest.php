<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use App\Http\Requests\AuthenticatedRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class ProfileUpdateRequest extends AuthenticatedRequest
{
    use ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->profileRules($this->authenticatedUser()->id);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => Str::squish($this->string('name')->toString()),
            'email' => Str::of($this->string('email')->toString())->trim()->lower()->toString(),
        ]);
    }
}
