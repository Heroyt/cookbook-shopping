<?php

declare(strict_types=1);

return [
    'disk' => env('MEDIA_DISK', 'local'),
    'root' => 'family-media',
    'max_kilobytes' => 5 * 1024,
    'max_width' => 8192,
    'max_height' => 8192,
    'max_pixels' => 25_000_000,
    'webp_quality' => 82,

    'types' => [
        'store-logo' => [
            'variants' => [
                'catalogue' => ['width' => 256, 'height' => 256],
            ],
        ],
        'store-section-icon' => [
            'variants' => [
                'catalogue' => ['width' => 128, 'height' => 128],
            ],
        ],
        'ingredient-photo' => [
            'variants' => [
                'catalogue' => ['width' => 480, 'height' => 480],
                'detail' => ['width' => 1280, 'height' => 1280],
            ],
        ],
        'recipe-cover' => [
            'variants' => [
                'catalogue' => ['width' => 640, 'height' => 360],
                'detail' => ['width' => 1600, 'height' => 900],
            ],
        ],
    ],
];
