<?php
return [
    'doctrine' => [
        'mongodb' => [
            'ns' => [
                'ValuFileStorage\\Model' => 'vendor/valu/valufilestorage/src/ValuFileStorage/Model',
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
                    'tmp' => 'data/filestorage/tmp',
                    'files' => 'data/filestorage/files',
                ]
            ],
        ],
        'ValuFileStorageAcl' => [
            'name' => 'FileStorage.Acl',
            'class' => 'ValuFileStorage\\Service\\Acl',
            'config' => 'vendor/valu/valufilestorage/config/acl.config.php',
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
