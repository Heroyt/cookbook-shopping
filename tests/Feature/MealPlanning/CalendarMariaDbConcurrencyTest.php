<?php

declare(strict_types=1);

namespace Tests\Feature\MealPlanning;

use App\Cookbook\Models\Recipe;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\MealPlanning\Models\CalendarEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

final class CalendarMariaDbConcurrencyTest extends TestCase
{
    public function test_concurrent_duplicate_creates_converge_on_one_exact_total(): void
    {
        $driver = DB::connection()->getDriverName();
        $databaseVersion = $driver === 'mysql' ? DB::scalar('select version()') : null;
        if (
            $driver !== 'mysql'
            || ! is_string($databaseVersion)
            || ! str_contains($databaseVersion, 'MariaDB')
        ) {
            $this->markTestSkipped('This compatibility proof requires disposable MariaDB.');
        }
        $firstMember = User::factory()->create();
        $secondMember = User::factory()->create();
        $family = Family::factory()->create();
        foreach ([$firstMember, $secondMember] as $member) {
            FamilyMembership::factory()->for($family)->for($member)->create();
            $member->forceFill(['current_family_id' => $family->id])->save();
        }
        $recipe = Recipe::factory()->for($family)->create();
        DB::disconnect();

        $processes = [];
        foreach ([$firstMember->id, $secondMember->id] as $userId) {
            $script = sprintf(
                'require %s; $app = require %s; $app->make(\\Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); '
                . '$user = \\App\\Models\\User::query()->findOrFail(%d); '
                . '$app->make(\\App\\MealPlanning\\Actions\\CreateCalendarEntry::class)->handle('
                . '$user, %d, "2026-08-12", null, \\App\\MealPlanning\\Values\\ServingCount::from("1.25"));',
                var_export(base_path('vendor/autoload.php'), true),
                var_export(base_path('bootstrap/app.php'), true),
                $userId,
                $recipe->id,
            );
            $process = new Process([PHP_BINARY, '-r', $script], base_path(), timeout: 30);
            $process->start();
            $processes[] = $process;
        }

        foreach ($processes as $process) {
            $this->assertSame(0, $process->wait(), $process->getErrorOutput());
        }

        DB::purge();
        $entries = CalendarEntry::query()
            ->where('family_id', $family->id)
            ->where('recipe_id', $recipe->id)
            ->where('date', '2026-08-12')
            ->where('meal_label_key', 'unlabeled')
            ->get();
        $this->assertCount(1, $entries);
        $this->assertSame('2.500000', $entries->firstOrFail()->serving_count);
    }
}
