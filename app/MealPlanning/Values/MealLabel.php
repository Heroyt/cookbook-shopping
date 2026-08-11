<?php

declare(strict_types=1);

namespace App\MealPlanning\Values;

enum MealLabel: string
{
    case Breakfast = 'snídaně';
    case MorningSnack = 'dopolední svačina';
    case Lunch = 'oběd';
    case AfternoonSnack = 'odpolední svačina';
    case Dinner = 'večeře';

    public static function nullableFromKey(string $key): ?self
    {
        return match ($key) {
            'breakfast' => self::Breakfast,
            'morning_snack' => self::MorningSnack,
            'lunch' => self::Lunch,
            'afternoon_snack' => self::AfternoonSnack,
            'dinner' => self::Dinner,
            'unlabeled' => null,
            default => null,
        };
    }

    public static function persistenceKey(?self $label): string
    {
        return $label?->key() ?? 'unlabeled';
    }

    public static function displayForKey(string $key): string
    {
        $label = self::nullableFromKey($key);

        return $label instanceof self ? $label->value : __('unlabeled');
    }

    public function key(): string
    {
        return match ($this) {
            self::Breakfast => 'breakfast',
            self::MorningSnack => 'morning_snack',
            self::Lunch => 'lunch',
            self::AfternoonSnack => 'afternoon_snack',
            self::Dinner => 'dinner',
        };
    }
}
