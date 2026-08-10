<?php

declare(strict_types=1);

namespace App\FamilyAccess\Http\Requests;

use App\Http\Requests\AuthenticatedRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

final class AddFamilyMemberRequest extends AuthenticatedRequest
{
    public function memberEmail(): string
    {
        return $this->string('email')->toString();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::of($this->string('email')->toString())->trim()->lower()->toString(),
        ]);
    }
}
