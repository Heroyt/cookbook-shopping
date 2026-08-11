<?php

declare(strict_types=1);

namespace App\MealPlanning\Values;

use Brick\Math\BigDecimal;
use InvalidArgumentException;

final readonly class ServingCount
{
    private function __construct(private BigDecimal $value) {}

    public static function from(string $value): self
    {
        if (preg_match('/^\d{1,14}(?:\.\d{1,6})?$/', $value) !== 1) {
            throw new InvalidArgumentException('Serving Count must be a canonical DECIMAL(20,6).');
        }

        $decimal = BigDecimal::of($value);
        if ($decimal->isLessThanOrEqualTo(0)) {
            throw new InvalidArgumentException('Serving Count must be positive.');
        }

        return new self($decimal);
    }

    public function plus(self $servingCount): self
    {
        return self::from(self::normalize((string) $this->value->plus($servingCount->value)));
    }

    public function toString(): string
    {
        return self::normalize((string) $this->value);
    }

    private static function normalize(string $value): string
    {
        if ( ! str_contains($value, '.')) {
            return $value;
        }

        return rtrim(rtrim($value, '0'), '.');
    }
}
