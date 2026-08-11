<?php

declare(strict_types=1);

namespace App\AgentIntegration\Http\Requests;

use App\Http\Requests\AuthenticatedRequest;

final class RotateAgentCredentialRequest extends AuthenticatedRequest
{
    /** @return array<string, never> */
    public function rules(): array
    {
        return [];
    }
}
