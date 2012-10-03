<?php
return [
    'roles' => [
        'guest' => [
            'id' => 'guest',
        ],
        'member' => [
            'id' => 'member',
        ],
        'admin' => [
            'id' => 'admin',
            'parents' => 'account-admin',
        ],
        'superuser' => [
            'id' => 'superuser',
            'parents' => 'admin',
        ],
    ],
    'resources' => [
        'file' => [
            'id' => 'file',
        ],
    ],
    'allow' => [
        'superuser' => [
            'resources' => 'file',
            'privileges' => [
                0 => 'read',
                1 => 'write',
                2 => 'batch-delete',
            ],
        ],
    ],
];
