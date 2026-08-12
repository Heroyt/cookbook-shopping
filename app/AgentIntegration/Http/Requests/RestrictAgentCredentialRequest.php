<?php

declare(strict_types=1);

namespace App\AgentIntegration\Http\Requests;

use App\AgentIntegration\AgentCredentialRestrictionAction;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class RestrictAgentCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function action(): AgentCredentialRestrictionAction
    {
        return AgentCredentialRestrictionAction::from($this->string('action')->toString());
    }

    public function expiresAt(): ?CarbonImmutable
    {
        $expiresAt = $this->validated('expires_at');

        return is_string($expiresAt) ? CarbonImmutable::parse($expiresAt, 'UTC') : null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::enum(AgentCredentialRestrictionAction::class)],
            'expires_at' => [
                Rule::requiredIf(fn (): bool => $this->input('action') === AgentCredentialRestrictionAction::ShortenExpiry->value),
                Rule::prohibitedIf(fn (): bool => $this->input('action') === AgentCredentialRestrictionAction::Revoke->value),
                'string',
                'date_format:Y-m-d\TH:i:s\Z',
                'after:now',
            ],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach (array_keys(Arr::except($this->all(), ['action', 'expires_at'])) as $field) {
                $validator->errors()->add((string) $field, 'The field is not supported.');
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'action.required' => 'The action field is required.',
            'action.enum' => 'The selected action is invalid.',
            'expires_at.required' => 'The expires_at field is required when shortening expiry.',
            'expires_at.prohibited' => 'The expires_at field must be omitted when revoking.',
            'expires_at.string' => 'The expires_at field must be a string.',
            'expires_at.date_format' => 'The expires_at field must be an RFC 3339 UTC timestamp using whole seconds.',
            'expires_at.after' => 'The expires_at field must be in the future.',
        ];
    }
}
