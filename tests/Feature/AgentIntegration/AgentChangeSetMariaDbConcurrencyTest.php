<?php

declare(strict_types=1);

namespace Tests\Feature\AgentIntegration;

use App\AgentIntegration\Actions\IssueAgentCredential;
use App\AgentIntegration\AgentCredentialAbility;
use App\AgentIntegration\Models\AgentChangeSet;
use App\FamilyAccess\AuthorizedFamilyContext;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

final class AgentChangeSetMariaDbConcurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->usesMariaDb()) {
            $this->artisan('migrate:fresh', ['--drop-views' => true]);
        }
    }

    protected function tearDown(): void
    {
        if ($this->usesMariaDb()) {
            $this->artisan('migrate:fresh', ['--drop-views' => true]);
        }

        parent::tearDown();
    }

    public function test_concurrent_preview_and_apply_retries_converge_on_one_immutable_result(): void
    {
        $driver = DB::connection()->getDriverName();
        $databaseVersion = $driver === 'mysql' ? DB::scalar('select version()') : null;
        if ($driver !== 'mysql' || ! is_string($databaseVersion) || ! str_contains($databaseVersion, 'MariaDB')) {
            $this->markTestSkipped('This compatibility proof requires disposable MariaDB.');
        }

        $issuer = User::factory()->create();
        $family = Family::factory()->create();
        FamilyMembership::factory()->create(['family_id' => $family->id, 'user_id' => $issuer->id]);
        $credential = app(IssueAgentCredential::class)->handle(
            new AuthorizedFamilyContext($issuer, $family),
            'Concurrent agent',
            [AgentCredentialAbility::CookbookWrite],
        )->credential;
        $document = [
            'version' => 1,
            'client_request_id' => 'concurrent-preview',
            'operations' => [[
                'operation_id' => 'create-store',
                'resource_type' => 'stores',
                'action' => 'create',
                'local_ref' => 'store',
                'data' => ['name' => 'Souběžný obchod'],
            ]],
        ];
        DB::disconnect();

        $previewProcesses = $this->startProcesses(2, $this->previewScript(
            $issuer->id,
            $family->id,
            $credential->id,
            $document,
        ));
        $previewIds = $this->successfulOutputs($previewProcesses);

        DB::purge();
        $this->assertCount(1, array_unique($previewIds));
        $changeSet = AgentChangeSet::query()->sole();
        $this->assertSame($changeSet->id, $previewIds[0]);
        DB::disconnect();

        $applyProcesses = $this->startProcesses(2, $this->applyScript(
            $issuer->id,
            $family->id,
            $credential->id,
            $changeSet->id,
            $changeSet->digest,
        ));
        $applyResults = $this->successfulOutputs($applyProcesses);

        DB::purge();
        $this->assertSame($applyResults[0], $applyResults[1]);
        $this->assertDatabaseCount('stores', 1);
        $this->assertDatabaseHas('stores', ['family_id' => $family->id, 'name' => 'Souběžný obchod']);
        $this->assertDatabaseHas('agent_change_sets', [
            'id' => $changeSet->id,
            'status' => 'applied',
            'outcome' => 'applied',
        ]);
    }

    private function usesMariaDb(): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return false;
        }

        $databaseVersion = DB::scalar('select version()');

        return is_string($databaseVersion) && str_contains($databaseVersion, 'MariaDB');
    }

    /** @return list<Process> */
    private function startProcesses(int $count, string $script): array
    {
        $processes = [];
        for ($index = 0; $index < $count; $index++) {
            $process = new Process([PHP_BINARY, '-r', $script], base_path(), timeout: 30);
            $process->start();
            $processes[] = $process;
        }

        return $processes;
    }

    /** @param list<Process> $processes @return list<string> */
    private function successfulOutputs(array $processes): array
    {
        $outputs = [];
        foreach ($processes as $process) {
            $this->assertSame(0, $process->wait(), $process->getErrorOutput());
            $outputs[] = trim($process->getOutput());
        }

        return $outputs;
    }

    /** @param array<string, mixed> $document */
    private function previewScript(int $userId, int $familyId, int $credentialId, array $document): string
    {
        return sprintf(
            'require %s; $app = require %s; $app->make(\\Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); '
            . '$user = \\App\\Models\\User::query()->findOrFail(%d); '
            . '$family = \\App\\FamilyAccess\\Models\\Family::query()->findOrFail(%d); '
            . '$credential = \\App\\AgentIntegration\\Models\\AgentCredential::query()->findOrFail(%d); '
            . '$document = %s; '
            . '$previewed = $app->make(\\App\\AgentIntegration\\ChangeSets\\PreviewAgentChangeSet::class)->handle('
            . 'new \\App\\FamilyAccess\\AuthorizedFamilyContext($user, $family), $credential, $document, strlen(json_encode($document, JSON_THROW_ON_ERROR))); '
            . 'echo $previewed->changeSet->id;',
            var_export(base_path('vendor/autoload.php'), true),
            var_export(base_path('bootstrap/app.php'), true),
            $userId,
            $familyId,
            $credentialId,
            var_export($document, true),
        );
    }

    private function applyScript(
        int $userId,
        int $familyId,
        int $credentialId,
        string $changeSetId,
        string $digest,
    ): string {
        return sprintf(
            'require %s; $app = require %s; $app->make(\\Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); '
            . '$user = \\App\\Models\\User::query()->findOrFail(%d); '
            . '$family = \\App\\FamilyAccess\\Models\\Family::query()->findOrFail(%d); '
            . '$credential = \\App\\AgentIntegration\\Models\\AgentCredential::query()->findOrFail(%d); '
            . '$changeSet = $app->make(\\App\\AgentIntegration\\ChangeSets\\ApplyAgentChangeSet::class)->handle('
            . 'new \\App\\FamilyAccess\\AuthorizedFamilyContext($user, $family), $credential, %s, %s, []); '
            . 'echo json_encode($changeSet->result_document, JSON_THROW_ON_ERROR);',
            var_export(base_path('vendor/autoload.php'), true),
            var_export(base_path('bootstrap/app.php'), true),
            $userId,
            $familyId,
            $credentialId,
            var_export($changeSetId, true),
            var_export($digest, true),
        );
    }
}
