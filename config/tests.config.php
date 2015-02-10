<?php
return [
    'doctrine' => [
        'configuration' => [
            'odm_default' => [
                'default_db' => 'valu_file_storage_test',
                'server' => '127.0.0.1'
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
                        'tmp' => __DIR__ .'/../tests/data/tmp',
                        'files' => __DIR__ .'/../tests/data/tmp',
                    ]
                ],
            ],
        ],
    ],
    'file_storage' => [
        'whitelist' => [
            'tmp' => '^file:///tmp',
            'tmp2' => '^file:///var/tmp',
            'tests' => '^file://.*/valufilestorage/tests/resources/.*',
            'dataurl' => '^data:',
        ]
    ],
];