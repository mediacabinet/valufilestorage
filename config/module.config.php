<?php
return [
    'doctrine' => [
        'driver' => [
            'odm_default' => [
                'drivers' => [
                    'ValuFileStorage\Model' => 'ValuFileStorage'
                ]
            ],
            'ValuFileStorage' => [
                'class' => 'Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver',
                'paths' => [
                    __DIR__ . '/../src/ValuFileStorage/Model'
                ]
            ]
        ]
    ],
    'valu_so' => [
        'services' => [
            'ValuFileStorageMongoFile' => [
                'name' => 'FileStorage.File',
                'factory' => 'ValuFileStorage\\Service\\MongoFileServiceFactory',
                'options' => [
                    'url_scheme' => 'mongofs',
                ],
            ],
            'ValuFileStorageLocalFile' => [
                'name' => 'FileStorage.File',
                'factory' => 'ValuFileStorage\\Service\\LocalFileServiceFactory',
                'options' => [
                    'url_scheme' => 'file',
                    'paths' => [
                        'tmp' => 'data/filestorage/tmp',
                        'files' => 'data/filestorage/files',
                    ]
                ],
            ],
        ],
    ],
    'file_storage' => [
        'whitelist' => [
            'tmp' => 'file:///var/tmp',
            'tmp2' => 'file:///tmp',
            'data' => 'file://' . realpath('data'),
            'tests' => 'file://.*/vendor/[^/]+/tests/resources/.*',
            'local' => 'http://zf2b\\.valu\\.fi/tests/.*',
            'dev'   => 'http://development.mediacabinet.fi/.*',
            'showell' => 'file:///var/www/showell/data/showell'
        ]
    ],
];
