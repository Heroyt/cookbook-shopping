<?php

declare(strict_types=1);

namespace App\AgentIntegration\Http\Requests;

use App\AgentIntegration\Catalog\CatalogResourceType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CatalogIndexRequest extends FormRequest
{
    public function resourceType(): ?CatalogResourceType
    {
        $resourceType = $this->validated('resource_type');

        return is_string($resourceType) ? CatalogResourceType::from($resourceType) : null;
    }

    public function status(): ?string
    {
        $status = $this->validated('status');

        return is_string($status) ? $status : null;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'resource_type' => ['sometimes', 'string', Rule::enum(CatalogResourceType::class)],
            'status' => ['sometimes', 'string', Rule::in(['active', 'archived'])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'resource_type.string' => 'The resource_type must be a string.',
            'resource_type.enum' => 'The selected resource_type is invalid.',
            'status.string' => 'The status must be a string.',
            'status.in' => 'The selected status is invalid.',
        ];
    }
}
