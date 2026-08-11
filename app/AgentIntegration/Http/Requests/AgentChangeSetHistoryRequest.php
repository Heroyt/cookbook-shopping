<?php

declare(strict_types=1);

namespace App\AgentIntegration\Http\Requests;

use App\AgentIntegration\Catalog\CatalogResourceType;
use App\Http\Requests\AuthenticatedRequest;
use Illuminate\Validation\Rule;

final class AgentChangeSetHistoryRequest extends AuthenticatedRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'credential_id' => ['nullable', 'integer', 'min:1'],
            'issuer_user_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'resource_type' => ['nullable', Rule::in(CatalogResourceType::values())],
            'outcome' => ['nullable', Rule::in(['applied'])],
        ];
    }

    /** @return array{credential_id: int|null, issuer_user_id: int|null, date_from: string|null, date_to: string|null, resource_type: string|null, outcome: string|null} */
    public function filters(): array
    {
        return [
            'credential_id' => $this->validatedInteger('credential_id'),
            'issuer_user_id' => $this->validatedInteger('issuer_user_id'),
            'date_from' => $this->validatedString('date_from'),
            'date_to' => $this->validatedString('date_to'),
            'resource_type' => $this->validatedString('resource_type'),
            'outcome' => $this->validatedString('outcome'),
        ];
    }

    private function validatedInteger(string $key): ?int
    {
        $value = $this->validated($key);

        return is_int($value) ? $value : null;
    }

    private function validatedString(string $key): ?string
    {
        $value = $this->validated($key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
