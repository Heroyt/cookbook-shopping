<?php

declare(strict_types=1);

namespace App\Cookbook\Values;

final readonly class EntityMediaDeletion
{
    /** @param array<string, string> $files */
    public function __construct(
        public string $directory,
        public array $files,
    ) {}
}
