<?php

return [
    'enable' => true,
    'database' => [
        'default' => 'mysql',
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', 3306),
                'database' => env('DB_DATABASE', 'webman'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => false,
                'engine' => null,
            ],
        ],
    ],
    'validation' => [
        'lang' => 'zh-CN',
        'custom_messages' => [],
    ],
    'pagination' => [
        'default_page_size' => 20,
        'max_page_size' => 100,
    ],
];