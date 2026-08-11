<?php

declare(strict_types=1);

use Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy;

return [
    'api_path' => 'api/v1',
    'api_domain' => null,
    'export_path' => 'api.json',
    'cache' => [
        'key' => 'scramble.agent-api.openapi',
        'store' => 'file',
    ],
    'info' => [
        'version' => '1.0.0',
        'description' => 'Versioned Family-scoped API for trusted agent integrations.',
    ],
    'ui' => [
        'title' => 'Agent API v1',
    ],
    'renderer' => 'elements',
    'renderers' => [
        'elements' => [
            'view' => 'scramble::docs',
            'theme' => 'light',
            'hideTryIt' => env('APP_ENV', 'production') === 'production',
            'hideSchemas' => false,
            'logo' => '',
            'tryItCredentialsPolicy' => 'omit',
            'layout' => 'responsive',
            'router' => 'hash',
        ],
    ],
    'servers' => null,
    'enum_cases_description_strategy' => 'description',
    'enum_cases_names_strategy' => false,
    'flatten_deep_query_parameters' => true,
    'middleware' => ['web'],
    'extensions' => [],
    'security_strategy' => MiddlewareAuthSecurityStrategy::class,
];
