<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\AgentIntegration\Models\AgentChangeSet;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

#[Signature('agent-change-sets:cleanup')]
#[Description('Expire old Agent Change Set previews and remove retained terminal previews.')]
final class CleanupAgentChangeSets extends Command
{
    public function handle(): int
    {
        $expired = AgentChangeSet::query()
            ->where('status', 'previewed')
            ->where('expires_at', '<=', now())
            ->update([
                'status' => 'expired',
                'terminal_at' => now(),
                'updated_at' => now(),
            ]);

        $terminalPreviews = AgentChangeSet::query()
            ->whereIn('status', ['expired', 'invalidated', 'stale'])
            ->where('terminal_at', '<=', now()->subHours(
                Config::integer('agent-integration.change_sets.terminal_retention_hours'),
            ));
        $removed = $terminalPreviews->count();
        $terminalPreviews->delete();

        $this->components->info("Expired {$expired} previews and removed {$removed} terminal previews.");

        return self::SUCCESS;
    }
}
