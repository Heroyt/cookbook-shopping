<?php

declare(strict_types=1);

namespace Tests\Feature\FamilyAccess;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class CreateUserCommandTest extends TestCase
{
    public function test_operator_can_create_a_user_with_a_hidden_password(): void
    {
        $this->artisan('user:create', [
            'email' => '  ALICE@example.com ',
            'name' => '  Alice   Example ',
        ])
            ->expectsQuestion('Password', 'safe-password')
            ->expectsQuestion('Confirm password', 'safe-password')
            ->expectsOutputToContain('Alice Example')
            ->assertSuccessful();

        $user = User::query()->sole();

        $this->assertSame('Alice Example', $user->name);
        $this->assertSame('alice@example.com', $user->email);
        $this->assertTrue(Hash::check('safe-password', $user->password));
    }

    public function test_command_rejects_a_duplicate_email_case_insensitively(): void
    {
        User::factory()->create(['email' => 'ALICE@example.com']);

        $this->artisan('user:create', [
            'email' => 'ALICE@example.com',
            'name' => 'Another Alice',
        ])
            ->expectsQuestion('Password', 'safe-password')
            ->expectsQuestion('Confirm password', 'safe-password')
            ->expectsOutputToContain('already been taken')
            ->assertFailed();

        $this->assertDatabaseCount((new User())->getTable(), 1);
    }
}
