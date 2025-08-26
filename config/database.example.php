<?php

/**
 * 多数据库连接配置示例
 * 
 * 这个文件展示了如何在webman项目中配置多个数据库连接
 * 支持完全的Yii2多数据库使用方式
 */

return [
    /**
     * 默认数据库配置
     * 对应Yii2中的 'db' 组件
     */
    'default' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 3306),
        'database' => env('DB_DATABASE', 'webman_main'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => false,
        'engine' => null,
        'options' => [
            PDO::ATTR_TIMEOUT => 60,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'"
        ],
    ],

    /**
     * 日志数据库配置
     * 专门用于存储系统日志、用户行为日志等
     */
    'log' => [
        'driver' => 'mysql',
        'host' => env('LOG_DB_HOST', 'log-server.company.com'),
        'port' => env('LOG_DB_PORT', 3306),
        'database' => env('LOG_DB_DATABASE', 'webman_logs'),
        'username' => env('LOG_DB_USERNAME', 'log_user'),
        'password' => env('LOG_DB_PASSWORD', 'log_password'),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => 'log_',
        'strict' => false,
        'engine' => null,
        'options' => [
            PDO::ATTR_TIMEOUT => 30,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ],
    ],

    /**
     * 统计数据库配置
     * 专门用于存储数据统计、报表等
     */
    'stats' => [
        'driver' => 'mysql',
        'host' => env('STATS_DB_HOST', 'stats-server.company.com'),
        'port' => env('STATS_DB_PORT', 3306),
        'database' => env('STATS_DB_DATABASE', 'webman_statistics'),
        'username' => env('STATS_DB_USERNAME', 'stats_user'),
        'password' => env('STATS_DB_PASSWORD', 'stats_password'),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => 'stats_',
        'strict' => false,
        'engine' => null,
        'options' => [
            PDO::ATTR_TIMEOUT => 60,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ],
    ],

    /**
     * 缓存数据库配置
     * 可以使用SQLite作为本地缓存
     */
    'cache' => [
        'driver' => 'sqlite',
        'database' => env('CACHE_DB_PATH', base_path('storage/cache/cache.sqlite')),
        'prefix' => '',
        'foreign_key_constraints' => true,
    ],

    /**
     * 读写分离配置示例
     * 主从数据库配置
     */
    'master_slave' => [
        'driver' => 'mysql',
        'read' => [
            'host' => [
                'slave1.company.com',
                'slave2.company.com',
            ],
        ],
        'write' => [
            'host' => ['master.company.com'],
        ],
        'sticky' => true,
        'database' => env('DB_DATABASE', 'webman_main'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ],

    /**
     * PostgreSQL数据库配置示例
     */
    'pgsql' => [
        'driver' => 'pgsql',
        'host' => env('PGSQL_HOST', 'localhost'),
        'port' => env('PGSQL_PORT', 5432),
        'database' => env('PGSQL_DATABASE', 'webman_pgsql'),
        'username' => env('PGSQL_USERNAME', 'postgres'),
        'password' => env('PGSQL_PASSWORD', ''),
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
        'schema' => 'public',
        'sslmode' => 'prefer',
    ],

    /**
     * MongoDB配置示例（需要安装mongodb扩展）
     */
    'mongodb' => [
        'driver' => 'mongodb',
        'host' => env('MONGO_HOST', 'localhost'),
        'port' => env('MONGO_PORT', 27017),
        'database' => env('MONGO_DATABASE', 'webman_mongo'),
        'username' => env('MONGO_USERNAME', ''),
        'password' => env('MONGO_PASSWORD', ''),
        'options' => [
            'database' => env('MONGO_AUTH_DATABASE', 'admin'),
        ],
    ],
];