<?php

declare(strict_types=1);

namespace App\Cookbook\Values;

enum EntityMediaFailure: string
{
    case ArchivedEntity = 'archived_entity';
    case AnimatedWebp = 'animated_webp';
    case UnsafeImage = 'unsafe_image';
    case StorageFailed = 'storage_failed';

    public function message(): string
    {
        return match ($this) {
            self::ArchivedEntity => 'Restore the entity before changing its image.',
            self::AnimatedWebp => 'Animated WebP images are not supported. Select a static image.',
            self::UnsafeImage => 'The image could not be decoded safely.',
            self::StorageFailed => 'The image could not be saved. Please try again.',
        };
    }
}
