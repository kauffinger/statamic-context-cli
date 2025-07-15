<?php

declare(strict_types=1);

// config for StatamicContext/StatamicContext
return [
    /*
    |--------------------------------------------------------------------------
    | Statamic Documentation Storage
    |--------------------------------------------------------------------------
    |
    | Configuration for storing and managing Statamic core documentation
    |
    */
    'docs' => [
        'storage_path' => storage_path('app/statamic-docs'),
        'index_file' => storage_path('app/statamic-docs/index.json'),
        'github_repo' => 'statamic/docs',
        'github_branch' => 'master',
        'collections' => [
            'docs',
            'tags',
            'modifiers',
            'fieldtypes',
            'variables',
            'reference',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Statamic Peak Documentation Storage
    |--------------------------------------------------------------------------
    |
    | Configuration for storing and managing Statamic Peak documentation
    |
    */
    'peak_docs' => [
        'storage_path' => storage_path('app/statamic-peak-docs'),
        'index_file' => storage_path('app/statamic-peak-docs/index.json'),
        'github_repo' => 'studio1902/statamic-peak-docs',
        'github_branch' => 'main',
        'collections' => [
            'docs',
            'docs/getting-started',
            'docs/features',
            'docs/other',
        ],
    ],
];
