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

    public function test_node_only_preparation_does_not_run_the_php_dependent_vite_build(): void
    {
        $this->assertStringNotContainsString(
            "command: 'pnpm install --frozen-lockfile && pnpm build'",
            $this->pipeline,
        );
    }

    public function test_vitest_uses_its_dedicated_configuration_in_ci(): void
    {
        $this->assertStringContainsString(
            "testCommand: 'pnpm test:node --maxWorkers=1 --testTimeout=10000'",
            $this->pipeline,
        );
    }

    public function test_phpunit_uses_the_project_php_memory_limit(): void
    {
        $configuration = file_get_contents(dirname(__DIR__, 2) . '/phpunit.xml');

        self::assertIsString($configuration);
        $this->assertStringContainsString('<ini name="memory_limit" value="256M"/>', $configuration);
    }
}
