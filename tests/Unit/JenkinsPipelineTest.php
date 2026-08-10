<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class JenkinsPipelineTest extends TestCase
{
    private string $pipeline;

    protected function setUp(): void
    {
        parent::setUp();

        $pipeline = file_get_contents(dirname(__DIR__, 2) . '/Jenkinsfile');

        self::assertIsString($pipeline);

        $this->pipeline = $pipeline;
    }

    public function test_wayfinder_form_variants_are_generated_before_checks(): void
    {
        $this->assertStringContainsString(
            'php artisan wayfinder:generate --with-form --no-interaction',
            $this->pipeline,
        );
    }

    public function test_frontend_assets_are_built_during_preparation(): void
    {
        $this->assertStringContainsString(
            "command: 'pnpm install --frozen-lockfile && pnpm build'",
            $this->pipeline,
        );
    }

    public function test_vitest_can_load_the_vite_configuration_in_ci(): void
    {
        $this->assertStringContainsString(
            "testCommand: 'LARAVEL_BYPASS_ENV_CHECK=1 pnpm test:node --maxWorkers=1 --testTimeout=10000'",
            $this->pipeline,
        );
    }
}
