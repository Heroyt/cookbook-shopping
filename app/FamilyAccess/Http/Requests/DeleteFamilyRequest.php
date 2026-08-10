<?php

declare(strict_types=1);

namespace App\FamilyAccess\Http\Requests;

use App\Http\Requests\AuthenticatedRequest;
use Illuminate\Contracts\Validation\ValidationRule;

final class DeleteFamilyRequest extends AuthenticatedRequest
{
    public function confirmedFamilyName(): string
    {
        return $this->string('family_name')->toString();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'family_name' => ['required', 'string', 'max:255'],
        ];
    }
}
