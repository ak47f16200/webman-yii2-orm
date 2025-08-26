<?php

/**
 * Webman Yii2 ORM Bootstrap
 * 
 * 在webman启动时初始化Yii2 ORM的多数据库连接配置
 * 
 * 使用方法：
 * 1. 将此文件复制到 config/bootstrap.php 中，或者
 * 2. 在现有的 bootstrap.php 中调用此文件的 init() 方法
 */

use Webman\Yii2Orm\Database\Connection;

class Yii2OrmBootstrap
{
    /**
     * 初始化Yii2 ORM
     * 
     * @throws Exception
     */
    public static function init()
    {
        // 加载助手函数
        require_once __DIR__ . '/../vendor/webman/yii2-orm/src/helpers/yii_compat.php';
        
        // 初始化多数据库连接
        self::setupDatabaseConnections();
        
        // 注册自动加载
        self::registerAutoload();
        
        echo "[Yii2 ORM] 多数据库连接初始化完成\n";
    }
    
    /**
     * 设置多数据库连接
     */
    protected static function setupDatabaseConnections()
    {
        // 加载数据库配置
        $databaseConfig = config('database', []);
        
        // 如果没有配置文件，使用默认配置
        if (empty($databaseConfig)) {
            $databaseConfig = self::getDefaultDatabaseConfig();
        }
        
        // 添加所有数据库连接
        foreach ($databaseConfig as $name => $config) {
            if (is_array($config) && !empty($config)) {
                Connection::addConnection($config, $name);
                echo "[Yii2 ORM] 已配置数据库连接: {$name}\n";
            }
        }
    }
    
    /**
     * 获取默认数据库配置
     * 
     * @return array
     */
    protected static function getDefaultDatabaseConfig()
    {
        return [
            'default' => [
                'driver' => 'mysql',
                'host' => env('DB_HOST', 'localhost'),
                'port' => env('DB_PORT', 3306),
                'database' => env('DB_DATABASE', 'webman'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => false,
                'engine' => null,
            ]
        ];
    }
    
    /**
     * 注册自动加载
     */
    protected static function registerAutoload()
    {
        // 这里可以注册额外的自动加载规则
        // 例如：Yii2 behavior、validator 等的自动加载
    }
    
    /**
     * 设置Yii2兼容环境变量
     */
    protected static function setupYii2Environment()
    {
        // 设置Yii2相关的环境变量或常量
        if (!defined('YII_DEBUG')) {
            define('YII_DEBUG', env('APP_DEBUG', false));
        }
        
        if (!defined('YII_ENV')) {
            define('YII_ENV', env('APP_ENV', 'prod'));
        }
    }
}

// 自动初始化（如果被直接包含）
if (class_exists('Webman\App')) {
    Yii2OrmBootstrap::init();
}

// 也可以手动调用
// Yii2OrmBootstrap::init();