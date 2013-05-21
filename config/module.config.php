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
            'tmp' => 'file://' . sys_get_temp_dir(),
        ]
    ],
];
