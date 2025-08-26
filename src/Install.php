<?php

namespace Webman\Yii2Orm;

/**
 * Webman Yii2 ORM 自动安装脚本
 * 
 * 当通过 composer 安装包时自动执行
 */

class Install
{
    /**
     * 安装插件
     */
    public static function install()
    {
        $configDir = base_path('config');
        
        if (!is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }
        
        $configFile = $configDir . '/yii2-orm.php';
        
        if (!file_exists($configFile)) {
            $defaultConfig = <<<'EOF'
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
EOF;
            
            file_put_contents($configFile, $defaultConfig);
            echo "Webman Yii2 ORM: 配置文件已创建 {$configFile}\n";
        }
        
        echo "Webman Yii2 ORM: 安装完成\n";
        echo "请在 config/bootstrap.php 中添加以下代码来初始化服务:\n";
        echo "\\Webman\\Yii2Orm\\ServiceProvider::autoInitialize();\n";
    }
    
    /**
     * 卸载插件
     */
    public static function uninstall()
    {
        $configFile = base_path('config/yii2-orm.php');
        
        if (file_exists($configFile)) {
            unlink($configFile);
            echo "Webman Yii2 ORM: 配置文件已删除\n";
        }
        
        echo "Webman Yii2 ORM: 卸载完成\n";
    }
}