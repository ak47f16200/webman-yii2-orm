<?php

/**
 * Yii2 兼容助手函数
 */

use Webman\Yii2Orm\Tools\DatabaseHelper;

if (!function_exists('yii_db')) {
    /**
     * 获取Yii2兼容的数据库连接
     * 
     * 使用方式：
     * yii_db()->createCommand($sql)->queryAll()
     * 
     * @return \Webman\Yii2Orm\Database\DatabaseConnection
     */
    function yii_db()
    {
        return new \Webman\Yii2Orm\Database\DatabaseConnection();
    }
}

if (!class_exists('Yii')) {
    /**
     * 模拟Yii类，提供基本的应用访问
     */
    class Yii
    {
        public static $app;
        
        public static function __callStatic($name, $args)
        {
            // 可以根据需要扩展更多方法
            return null;
        }
    }
    
    // 初始化模拟的Yii::$app
    Yii::$app = new class {
        public $db;
        
        public function __construct()
        {
            $this->db = yii_db();
        }
        
        public function __get($name)
        {
            if ($name === 'db') {
                return $this->db;
            }
            return null;
        }
        
        /**
         * 获取数据库连接 - 兼容 \Yii::$app->getDb() 写法
         * 
         * @return object
         */
        public function getDb()
        {
            return $this->db;
        }
    };
}

if (!function_exists('db_command')) {
    /**
     * 快速创建数据库命令的助手函数
     * 
     * @param string $sql SQL语句
     * @param array $params 参数
     * @return \Webman\Yii2Orm\Tools\DatabaseCommand
     */
    function db_command($sql = '', $params = [])
    {
        return DatabaseHelper::createCommand($sql, $params);
    }
}

if (!function_exists('db_query')) {
    /**
     * 快速执行查询的助手函数
     * 
     * @param string $sql SQL语句
     * @param array $params 参数
     * @return array
     */
    function db_query($sql, $params = [])
    {
        return DatabaseHelper::createCommand($sql, $params)->queryAll();
    }
}

if (!function_exists('db_query_one')) {
    /**
     * 快速执行查询并返回单行的助手函数
     * 
     * @param string $sql SQL语句
     * @param array $params 参数
     * @return array|null
     */
    function db_query_one($sql, $params = [])
    {
        return DatabaseHelper::createCommand($sql, $params)->queryOne();
    }
}

if (!function_exists('db_execute')) {
    /**
     * 快速执行非查询SQL的助手函数
     * 
     * @param string $sql SQL语句
     * @param array $params 参数
     * @return int
     */
    function db_execute($sql, $params = [])
    {
        return DatabaseHelper::createCommand($sql, $params)->execute();
    }
}