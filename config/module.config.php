<?php
return [
    'doctrine' => [
        'mongodb' => [
            'ns' => [
                'ValuFileStorage\\Model' => 'module/ValuFileStorage/src/ValuFileStorage/Model',
            ],
        ],
    ],
    'service_manager' => [
        'factories' => [
            'ValuFileStorageDm' => 'ValuFileStorage\\ServiceManager\\DocumentManagerFactory',
        ],
    ],
    'services' => [
        'ValuFileStorageMongoFile' => [
            'name' => 'FileStorage.File',
            'factory' => 'ValuFileStorage\\ServiceManager\\MongoFileServiceFactory',
            'options' => [
                'url_scheme' => 'mongofs',
            ],
        ],
        'ValuFileStorageLocalFile' => [
            'name' => 'FileStorage.File',
            'factory' => 'ValuFileStorage\\ServiceManager\\LocalFileServiceFactory',
            'options' => [
                'url_scheme' => 'file',
                'paths' => [
                    'tmp' => realpath(__DIR__ . '/../../../data/filestorage/tmp')
                ]
            ],
        ],
        'ValuFileStorageAcl' => [
            'name' => 'FileStorage.Acl',
            'class' => 'ValuFileStorage\\Service\\Acl',
            'config' => 'module/ValuFileStorage/config/acl.config.php',
        ],
    ],
    'file_storage' => [
        'whitelist' => [
            'tmp' => 'file:///var/tmp',
            'tmp2' => 'file:///tmp',
            'data' => 'file://' . realpath(__DIR__ . '/../../../data'),
            'tests' => 'file://.*/module/[^/]+/tests/resources/.*',
            'local' => 'http://zf2b\\.valu\\.fi/tests/.*',
            'dev'   => 'http://development.mediacabinet.fi/.*'
        ]
    ],
];
