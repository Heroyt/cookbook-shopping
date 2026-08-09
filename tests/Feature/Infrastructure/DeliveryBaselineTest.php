<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class DeliveryBaselineTest extends TestCase
{
    private string $workingDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workingDirectory = storage_path('framework/testing/delivery-baseline-' . bin2hex(random_bytes(8)));

        File::ensureDirectoryExists($this->workingDirectory . '/bin');
        File::ensureDirectoryExists($this->workingDirectory . '/bootstrap/cache');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->workingDirectory);

        parent::tearDown();
    }

    public function test_development_entrypoint_stops_when_migrations_fail(): void
    {
        $this->writeExecutable('php', <<<'SH'
#!/usr/bin/env sh
if [ "$1" = "artisan" ] && [ "$2" = "migrate" ]; then
    exit 71
fi

exit 0
SH);

        $process = $this->runEntrypoint('docker/dev/start');

        $this->assertSame(71, $process->getExitCode());
        $this->assertStringContainsString('Running migrations...', $process->getOutput());
        $this->assertStringNotContainsString('Starting scheduler', $process->getOutput());
        $this->assertStringNotContainsString('Starting supervisor', $process->getOutput());
    }

    public function test_production_entrypoint_stops_when_migrations_fail(): void
    {
        $this->writeExecutable('chown', <<<'SH'
#!/usr/bin/env sh
exit 0
SH);
        $this->writeExecutable('php', <<<'SH'
#!/usr/bin/env sh
if [ "$1" = "artisan" ] && [ "$2" = "migrate" ]; then
    exit 72
fi

exit 0
SH);

        $process = $this->runEntrypoint('docker/production/start');

        $this->assertSame(72, $process->getExitCode());
        $this->assertStringContainsString('Running migrations...', $process->getOutput());
        $this->assertStringNotContainsString('Starting scheduler', $process->getOutput());
        $this->assertStringNotContainsString('Starting PHP-FPM', $process->getOutput());
    }

    public function test_setup_and_images_use_the_declared_package_manager_without_build_time_environment(): void
    {
        /** @var array{packageManager: string} $packageManifest */
        $packageManifest = json_decode(File::get(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);
        /** @var array{scripts: array{setup: list<string>, ci:check: list<string>}} $composerManifest */
        $composerManifest = json_decode(File::get(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);
        $setupCommands = implode("\n", $composerManifest['scripts']['setup']);
        $ciCommands = implode("\n", $composerManifest['scripts']['ci:check']);
        $developmentDockerfile = File::get(base_path('docker/dev/Dockerfile'));
        $productionDockerfile = File::get(base_path('docker/production/Dockerfile'));
        $dockerIgnore = File::get(base_path('.dockerignore'));

        $this->assertSame('pnpm@11.17.0', $packageManifest['packageManager']);
        $this->assertStringContainsString("touch('database/database.sqlite')", $setupCommands);
        $this->assertStringContainsString('pnpm install --frozen-lockfile', $setupCommands);
        $this->assertStringContainsString('pnpm run build', $setupCommands);
        $this->assertDoesNotMatchRegularExpression('/(?:^|\n)npm /', $setupCommands . "\n" . $ciCommands);
        $this->assertStringContainsString('pnpm@11.17.0', $developmentDockerfile);
        $this->assertStringContainsString('pnpm@11.17.0', $productionDockerfile);
        $this->assertStringContainsString('pnpm install --frozen-lockfile', $productionDockerfile);
        $this->assertStringNotContainsString('pnpm@latest', $productionDockerfile);
        $this->assertStringNotContainsString('COPY .env', $productionDockerfile);
        $this->assertStringNotContainsString('php artisan config:cache', $productionDockerfile);
        $this->assertStringContainsString("\n.env*\n", "\n{$dockerIgnore}\n");
        $this->assertStringContainsString("\n!.env.example\n", "\n{$dockerIgnore}\n");
    }

    public function test_database_environment_contract_uses_sqlite_locally_and_mariadb_in_production(): void
    {
        $localEnvironment = File::get(base_path('.env.example'));
        $productionEnvironment = File::get(base_path('.env.production.example'));
        $phpUnitConfiguration = File::get(base_path('phpunit.xml'));
        $databaseConfiguration = File::get(base_path('config/database.php'));

        $this->assertMatchesRegularExpression('/^DB_CONNECTION=sqlite$/m', $localEnvironment);
        $this->assertStringContainsString('<env name="DB_CONNECTION" value="sqlite"/>', $phpUnitConfiguration);
        $this->assertMatchesRegularExpression('/^APP_ENV=production$/m', $productionEnvironment);
        $this->assertMatchesRegularExpression('/^APP_DEBUG=false$/m', $productionEnvironment);
        $this->assertMatchesRegularExpression('/^DB_CONNECTION=mariadb$/m', $productionEnvironment);
        $this->assertMatchesRegularExpression('/^MAIL_SCHEME=smtp$/m', $productionEnvironment);
        $this->assertStringContainsString("'mariadb' => [", $databaseConfiguration);
        $this->assertStringContainsString("'driver' => 'mariadb'", $databaseConfiguration);
    }

    private function runEntrypoint(string $path): Process
    {
        $process = new Process(
            ['/bin/sh', base_path($path)],
            $this->workingDirectory,
            [
                'APP_DEBUG' => 'false',
                'APP_ENV' => 'production',
                'DB_CONNECTION' => 'testing',
                'PATH' => $this->workingDirectory . '/bin:/usr/bin:/bin',
            ],
        );
        $process->run();

        return $process;
    }

    private function writeExecutable(string $name, string $contents): void
    {
        $path = $this->workingDirectory . '/bin/' . $name;

        File::put($path, $contents);
        chmod($path, 0755);
    }
}
