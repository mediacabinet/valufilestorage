<?php
return [
    'doctrine' => [
        'configuration' => [
            'odm_default' => [
                'default_db' => 'valu_file_system_test'
            ]
        ]
    ],
    'valu_so' => [
        // Ensure that proxy classes are re-generated when they change
        'proxy_auto_create_strategy' => 'mtime',
        'services' => [
            'ValuFileStorageLocalFile' => [
                'options' => [
                    'paths' => [
                        'tmp' => realpath(__DIR__ .'/../tests/tmp'),
                        'files' => realpath(__DIR__ .'/../tests/tmp'),
                    ]
                ],
            ],
        ],
    ],
    'file_storage' => [
        'whitelist' => [
            'tmp' => 'file:///tmp',
            'tmp2' => 'file:///var/tmp',
            'tests' => 'file://.*/vendor/valu/valufilestorage/tests/resources/.*',
        ]
    ],
];