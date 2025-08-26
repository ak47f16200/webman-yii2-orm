<?php

namespace Webman\Yii2Orm;

use Webman\Yii2Orm\Database\Connection;

class ServiceProvider
{
    protected static $initialized = false;
    
    /**
     * 初始化服务
     */
    public static function initialize(array $config = [])
    {
        if (static::$initialized) {
            return;
        }
        
        // 初始化数据库连接
        if (isset($config['database'])) {
            Connection::initialize($config['database']);
        }
        
        static::$initialized = true;
    }
    
    /**
     * 获取配置
     */
    public static function getConfig()
    {
        $configPath = base_path('config/yii2-orm.php');
        
        if (file_exists($configPath)) {
            return include $configPath;
        }
        
        return [];
    }
    
    /**
     * 自动初始化
     */
    public static function autoInitialize()
    {
        $config = static::getConfig();
        static::initialize($config);
    }
}