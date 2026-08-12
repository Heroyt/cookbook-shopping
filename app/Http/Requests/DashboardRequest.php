<?php

declare(strict_types=1);

namespace App\Http\Requests;

final class DashboardRequest extends AuthenticatedRequest
{
    /** @return array<string, never> */
    public function rules(): array
    {
        return [];
    }
}
