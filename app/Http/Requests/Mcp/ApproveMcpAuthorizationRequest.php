<?php

declare(strict_types=1);

namespace App\Http\Requests\Mcp;

use App\AgentIntegration\AgentCredentialAbility;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ApproveMcpAuthorizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'auth_token' => ['required', 'string'],
            'abilities' => ['sometimes', 'array'],
            'abilities.*' => [
                'distinct',
                Rule::in([
                    AgentCredentialAbility::CookbookWrite->value,
                    AgentCredentialAbility::PlanningWrite->value,
                    AgentCredentialAbility::DestructiveWrite->value,
                ]),
            ],
        ];
    }

    /** @return list<AgentCredentialAbility> */
    public function abilities(): array
    {
        $values = $this->validated('abilities', []);
        if ( ! is_array($values)) {
            return [];
        }

        $abilities = [];
        foreach ($values as $value) {
            if (is_string($value)) {
                $abilities[] = AgentCredentialAbility::from($value);
            }
        }

        return $abilities;
    }
}
