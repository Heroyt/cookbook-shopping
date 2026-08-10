<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class LocalizationTest extends TestCase
{
    public function test_czech_is_the_application_and_fallback_locale(): void
    {
        $this->assertSame('Kuchařka', config('app.name'));
        $this->assertSame('cs', config('app.locale'));
        $this->assertSame('cs', config('app.fallback_locale'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<html lang="cs"', escape: false);
    }

    public function test_framework_and_application_messages_are_available_in_czech(): void
    {
        $validator = Validator::make(
            ['email' => 'neni-email'],
            ['email' => ['required', 'email']],
        );

        $this->assertSame('Tyto přihlašovací údaje neodpovídají našim záznamům.', __('auth.failed'));
        $this->assertSame('Odkaz pro obnovení hesla jsme vám poslali e-mailem.', __('passwords.sent'));
        $this->assertSame('Rodina byla vytvořena.', __('Family created.'));
        $this->assertSame('Pole e-mail musí obsahovat platnou e-mailovou adresu.', $validator->errors()->first('email'));
    }

    public function test_every_framework_validation_rule_has_a_czech_translation(): void
    {
        /** @var array<string, mixed> $englishValidation */
        $englishValidation = require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php');
        /** @var array<string, mixed> $czechValidation */
        $czechValidation = require lang_path('cs/validation.php');

        $this->assertSame([], array_values(array_diff(array_keys($englishValidation), array_keys($czechValidation))));
    }
}
