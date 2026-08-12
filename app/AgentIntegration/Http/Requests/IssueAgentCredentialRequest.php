<?php

declare(strict_types=1);

namespace App\AgentIntegration\Http\Requests;

use App\AgentIntegration\AgentCredentialAbility;
use App\Http\Requests\AuthenticatedRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;
use LogicException;

final class IssueAgentCredentialRequest extends AuthenticatedRequest
{
    /** @var list<int> */
    private const array VALIDITY_DAY_PRESETS = [1, 7, 30, 90, 180, 365];

    /** @return list<AgentCredentialAbility> */
    public function credentialAbilities(): array
    {
        $abilities = $this->validated('abilities', []);

        if ( ! is_array($abilities)) {
            throw new LogicException('Validated Agent Credential abilities must be an array.');
        }

        return array_values(array_map(function (mixed $ability): AgentCredentialAbility {
            if ( ! is_string($ability)) {
                throw new LogicException('Validated Agent Credential ability must be a string.');
            }

            return AgentCredentialAbility::from($ability);
        }, $abilities));
    }

    public function credentialName(): string
    {
        return $this->string('name')->trim()->toString();
    }

    public function expiresAt(): ?Carbon
    {
        $validityDays = $this->validated('validity_days');
        if (is_int($validityDays)) {
            return Carbon::now()->addDays($validityDays);
        }

        $expiresAt = $this->validated('expires_at');

        return is_string($expiresAt)
            ? Carbon::parse($expiresAt, Config::string('app.timezone'))->startOfDay()->addDay()
            : null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['sometimes', 'array', 'list'],
            'abilities.*' => [
                Rule::enum(AgentCredentialAbility::class)->only([
                    AgentCredentialAbility::CookbookWrite,
                    AgentCredentialAbility::PlanningWrite,
                    AgentCredentialAbility::DestructiveWrite,
                ]),
                'distinct',
            ],
            'expires_at' => [
                'nullable',
                Rule::prohibitedIf(fn (): bool => $this->filled('validity_days')),
                Rule::date()->format('Y-m-d')->todayOrAfter()->beforeOrEqual(
                    today()->addDays(Config::integer('agent-integration.credentials.max_expiry_days') - 1),
                ),
            ],
            'validity_days' => [
                'nullable',
                'integer',
                Rule::prohibitedIf(fn (): bool => $this->filled('expires_at')),
                Rule::in(self::VALIDITY_DAY_PRESETS),
            ],
        ];
    }
}
