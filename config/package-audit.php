<?php

declare(strict_types=1);

return [
    'checks' => [
        'composer_validate' => true,
        'pint' => true,
        'phpstan' => [
            'enabled' => true,
            'level' => 8,
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
    'certificate_filename' => 'AUDIT.json',
];
