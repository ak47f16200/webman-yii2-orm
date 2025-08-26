<?php

namespace Webman\Yii2Orm\Database;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection as IlluminateConnection;

class Connection
{
    protected static $capsule;
    protected static $connections = [];
    protected static $initialized = false;
    
    /**
     * 初始化数据库连接
     */
    public static function initialize(array $config = [])
    {
        if (static::$initialized) {
            return static::$capsule;
        }
        
        static::$capsule = new Capsule;
        
        // 如果传入的是多个连接配置
        if (isset($config['connections'])) {
            // 多数据库配置
            foreach ($config['connections'] as $name => $connectionConfig) {
                static::$capsule->addConnection($connectionConfig, $name);
            }
            
            // 设置默认连接
            if (isset($config['default'])) {
                static::$capsule->getDatabaseManager()->setDefaultConnection($config['default']);
            }
        } else {
            // 单数据库配置
            $defaultConfig = [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => 3306,
                'database' => 'webman',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
            ];
            
            $config = array_merge($defaultConfig, $config);
            static::$capsule->addConnection($config);
        }
        
        static::$capsule->setAsGlobal();
        static::$capsule->bootEloquent();
        
        static::$initialized = true;
        
        return static::$capsule;
    }
    
    /**
     * 获取数据库连接实例
     * 
     * @param string|null $name 连接名称，null为默认连接
     * @return IlluminateConnection
     */
    public static function getInstance($name = null): IlluminateConnection
    {
        if (!static::$initialized) {
            static::initialize();
        }
        
        return static::$capsule->getConnection($name);
    }
    
    /**
     * 添加新的数据库连接
     * 
     * @param array $config 连接配置
     * @param string $name 连接名称
     */
    public static function addConnection(array $config, $name = 'default')
    {
        if (!static::$initialized) {
            static::initialize();
        }
        
        static::$capsule->addConnection($config, $name);
        static::$connections[$name] = $config;
    }
    
    /**
     * 获取查询构建器
     * 
     * @param string $table 表名
     * @param string|null $connection 连接名称
     */
    public static function table($table, $connection = null)
    {
        return static::getInstance($connection)->table($table);
    }
    
    /**
     * 开始事务
     * 
     * @param string|null $connection 连接名称
     */
    public static function beginTransaction($connection = null)
    {
        return static::getInstance($connection)->beginTransaction();
    }
    
    /**
     * 提交事务
     * 
     * @param string|null $connection 连接名称
     */
    public static function commit($connection = null)
    {
        return static::getInstance($connection)->commit();
    }
    
    /**
     * 回滚事务
     * 
     * @param string|null $connection 连接名称
     */
    public static function rollback($connection = null)
    {
        return static::getInstance($connection)->rollback();
    }
    
    /**
     * 执行事务
     * 
     * @param callable $callback 事务回调
     * @param string|null $connection 连接名称
     */
    public static function transaction(callable $callback, $connection = null)
    {
        return static::getInstance($connection)->transaction($callback);
    }
    
    /**
     * 获取所有连接配置
     */
    public static function getConnections()
    {
        return static::$connections;
    }
    
    /**
     * 检查连接是否存在
     */
    public static function hasConnection($name)
    {
        return isset(static::$connections[$name]);
    }
}