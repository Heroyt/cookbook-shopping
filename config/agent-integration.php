<?php

declare(strict_types=1);

return [
    'credentials' => [
        'default_expiry_days' => 90,
        'max_expiry_days' => 365,
    ],
    'change_sets' => [
        'max_operations' => 250,
        'max_payload_bytes' => 2 * 1024 * 1024,
        'preview_expiry_hours' => 24,
        'terminal_retention_hours' => 24,
    ],
    'rates' => [
        'catalog_per_minute' => 120,
        'preview_per_minute' => 20,
        'apply_per_minute' => 10,
        'credential_restriction_per_minute' => 10,
    ],
];
