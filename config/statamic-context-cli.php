<?php

declare(strict_types=1);

// config for StatamicContext/StatamicContext
return [
    /*
    |--------------------------------------------------------------------------
    | Documentation Storage
    |--------------------------------------------------------------------------
    |
    | Configuration for storing and managing Statamic documentation
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
];
