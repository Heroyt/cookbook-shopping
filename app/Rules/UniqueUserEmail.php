<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

final class UniqueUserEmail implements ValidationRule
{
    public function __construct(private readonly ?int $ignoredUserId = null) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ( ! is_string($value)) {
            return;
        }

        $query = User::query()
            ->whereRaw('LOWER(email) = ?', [Str::lower($value)]);

        if ($this->ignoredUserId !== null) {
            $query->whereKeyNot($this->ignoredUserId);
        }

        if ($query->exists()) {
            $fail(__('validation.unique', ['attribute' => $attribute]));
        }
    }
}
