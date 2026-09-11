<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Quality and Certification Checks
    |--------------------------------------------------------------------------
    |
    | Configure which checks are executed during an audit. Each check can
    | be individually toggled or customized with specific parameters.
    |
    */
    'checks' => [
        'composer_validate' => true,
        'pint' => true,
        'phpstan' => [
            'enabled' => true,
            'level' => 8,
            'memory_limit' => '1G',
        ],
        'tests' => true,
        'isolated' => true,
        'readme' => [
            'enabled' => true,
            'required_sections' => [
                'Requirements',
                'Installation',
                'Usage',
                'Testing',
                'License',
            ],
        ],
        'export_ignore' => true,
        'git_cleanliness' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeouts (Seconds)
    |--------------------------------------------------------------------------
    |
    | Execution timeouts for long-running processes such as isolated composer
    | installations and full test suite executions.
    |
    */
    'timeouts' => [
        'isolated' => 300,
        'tests' => 120,
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Environment Variables
    |--------------------------------------------------------------------------
    |
    | Environment variables injected when executing test suites to prevent
    | host database/cache pollution.
    |
    */
    'test_environment' => [
        'APP_ENV' => 'testing',
        'CACHE_STORE' => 'array',
        'SESSION_DRIVER' => 'array',
        'QUEUE_CONNECTION' => 'sync',
        'MAIL_MAILER' => 'array',
    ],

    /*
    |--------------------------------------------------------------------------
    | Git Automation Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for automated tagging and committing audit certificates.
    |
    */
    'git' => [
        'tag_prefix' => 'audit/v',
        'commit_message' => 'Audit certificate for v{version}',
    ],

    /*
    |--------------------------------------------------------------------------
    | Certificate Filename
    |--------------------------------------------------------------------------
    |
    | The file name of the tamper-proof cryptographic certificate written
    | to the package root.
    |
    */
    'certificate_filename' => 'AUDIT.json',
];
