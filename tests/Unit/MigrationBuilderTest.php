<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class MigrationBuilderTest extends TestCase
{
    public function test_migrations_do_not_execute_raw_sql_statements(): void
    {
        $migrationPaths = glob(dirname(__DIR__, 2) . '/database/migrations/*.php');

        self::assertNotFalse($migrationPaths);

        foreach ($migrationPaths as $migrationPath) {
            $migration = file_get_contents($migrationPath);

            self::assertIsString($migration);
            self::assertDoesNotMatchRegularExpression(
                '/(?:<<<[\'\"]?SQL|[\'\"]\s*(?:CREATE|ALTER|DROP|INSERT|UPDATE|DELETE|SELECT)\b|'
                . 'DB::(?:raw|statement|unprepared|select|insert|update|delete|affectingStatement)\s*\(|'
                . '->(?:selectRaw|whereRaw|havingRaw|orderByRaw|groupByRaw|fromRaw|virtualAs|storedAs)\s*\()/i',
                $migration,
                sprintf('%s contains a raw SQL statement.', basename($migrationPath)),
            );
        }
    }
}
