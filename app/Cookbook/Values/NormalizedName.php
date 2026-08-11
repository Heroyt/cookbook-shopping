<?php

declare(strict_types=1);

namespace App\Cookbook\Values;

use Illuminate\Support\Str;

final readonly class NormalizedName
{
    private function __construct(
        public string $display,
        public string $key,
    ) {}

    public static function from(string $value): self
    {
        $display = Str::squish($value);

        return new self($display, Str::lower($display));
    }

    public static function compare(
        string $leftKey,
        int $leftId,
        string $rightKey,
        int $rightId,
    ): int {
        $keyComparison = strcmp($leftKey, $rightKey);

        return $keyComparison !== 0 ? $keyComparison : $leftId <=> $rightId;
    }
}
