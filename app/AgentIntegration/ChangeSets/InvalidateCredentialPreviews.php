<?php

declare(strict_types=1);

namespace App\AgentIntegration\ChangeSets;

use App\AgentIntegration\Models\AgentChangeSet;
use App\AgentIntegration\Models\AgentCredential;

final readonly class InvalidateCredentialPreviews
{
    public function handle(AgentCredential $credential): int
    {
        return AgentChangeSet::query()
            ->whereBelongsTo($credential, 'credential')
            ->where('status', 'previewed')
            ->update([
                'status' => 'invalidated',
                'terminal_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
