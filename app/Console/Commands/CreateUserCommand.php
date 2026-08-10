<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use App\Rules\UniqueUserEmail;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

#[Signature('user:create {email : The User email address} {name : The User display name}')]
#[Description('Create a User while public registration is disabled')]
final class CreateUserCommand extends Command
{
    use PasswordValidationRules;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $input = [
            'name' => Str::squish((string) $this->argument('name')),
            'email' => Str::of((string) $this->argument('email'))->trim()->lower()->toString(),
            'password' => $this->askForPassword('Password'),
            'password_confirmation' => $this->askForPassword('Confirm password'),
        ];

        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                new UniqueUserEmail(),
            ],
            'password' => $this->passwordRules(),
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return SymfonyCommand::FAILURE;
        }

        $user = User::query()->create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        $this->components->info("User {$user->name} <{$user->email}> created.");

        return SymfonyCommand::SUCCESS;
    }

    private function askForPassword(string $question): string
    {
        $answer = $this->secret($question);

        return is_string($answer) ? $answer : '';
    }
}
