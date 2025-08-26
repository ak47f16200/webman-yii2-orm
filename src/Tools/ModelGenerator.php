<?php

namespace Webman\Yii2Orm\Tools;

use Webman\Yii2Orm\Database\Connection;

class ModelGenerator
{
    protected $connection;
    protected $namespace = 'app\\model';
    protected $baseClass = '\\Webman\\Yii2Bridge\\ActiveRecord\\ActiveRecord';
    
    public function __construct($connection = null, $namespace = null, $baseClass = null)
    {
        $this->connection = $connection ?: Connection::getInstance();
        
        if ($namespace !== null) {
            $this->namespace = $namespace;
        }
        
        if ($baseClass !== null) {
            $this->baseClass = $baseClass;
        }
    }
    
    /**
     * 生成模型代码
     */
    public function generate($tableName, $className = null)
    {
        if ($className === null) {
            $className = $this->generateClassName($tableName);
        }
        
        $columns = $this->getTableColumns($tableName);
        $primaryKey = $this->getPrimaryKey($tableName);
        
        $code = $this->generateModelCode($className, $tableName, $columns, $primaryKey);
        
        return $code;
    }
    
    /**
     * 生成类名
     */
    protected function generateClassName($tableName)
    {
        // 移除表前缀
        $name = preg_replace('/^[a-z]+_/', '', $tableName);
        
        // 转换为驼峰命名
        return str_replace('_', '', ucwords($name, '_'));
    }
    
    /**
     * 获取表字段信息
     */
    protected function getTableColumns($tableName)
    {
        $columns = $this->connection->getDoctrineSchemaManager()->listTableColumns($tableName);
        
        $result = [];
        foreach ($columns as $column) {
            $result[] = [
                'name' => $column->getName(),
                'type' => $column->getType()->getName(),
                'nullable' => !$column->getNotnull(),
                'default' => $column->getDefault(),
                'comment' => $column->getComment(),
            ];
        }
        
        return $result;
    }
    
    /**
     * 获取主键
     */
    protected function getPrimaryKey($tableName)
    {
        $indexes = $this->connection->getDoctrineSchemaManager()->listTableIndexes($tableName);
        
        foreach ($indexes as $index) {
            if ($index->isPrimary()) {
                $columns = $index->getColumns();
                return $columns[0] ?? 'id';
            }
        }
        
        return 'id';
    }
    
    /**
     * 生成模型代码
     */
    protected function generateModelCode($className, $tableName, $columns, $primaryKey)
    {
        $properties = [];
        $rules = [];
        $fillable = [];
        
        foreach ($columns as $column) {
            $name = $column['name'];
            $type = $this->mapPhpType($column['type']);
            $comment = $column['comment'] ? ' ' . $column['comment'] : '';
            
            $properties[] = " * @property {$type} \${$name}{$comment}";
            
            if (!$column['nullable'] && $column['default'] === null && $name !== $primaryKey) {
                $rules[] = "'{$name}' => 'required'";
            }
            
            if ($name !== $primaryKey) {
                $fillable[] = "'{$name}'";
            }
        }
        
        $propertiesStr = implode("\n", $properties);
        $rulesStr = implode(",\n            ", $rules);
        $fillableStr = implode(", ", $fillable);
        
        $code = "<?php

namespace {$this->namespace};

use {$this->baseClass};

/**
{$propertiesStr}
 */
class {$className} extends ActiveRecord
{
    protected \$table = '{$tableName}';
    protected \$primaryKey = '{$primaryKey}';
    protected \$fillable = [{$fillableStr}];
    
    public function rules()
    {
        return [
            {$rulesStr}
        ];
    }
    
    public function attributeLabels()
    {
        return [
            // 在这里定义字段标签
        ];
    }
}";
        
        return $code;
    }
    
    /**
     * 映射 PHP 类型
     */
    protected function mapPhpType($dbType)
    {
        $typeMap = [
            'integer' => 'int',
            'bigint' => 'int',
            'smallint' => 'int',
            'tinyint' => 'int',
            'decimal' => 'float',
            'float' => 'float',
            'double' => 'float',
            'boolean' => 'bool',
            'date' => 'string',
            'datetime' => 'string',
            'timestamp' => 'string',
            'time' => 'string',
            'text' => 'string',
            'json' => 'array',
        ];
        
        return $typeMap[$dbType] ?? 'string';
    }
    
    /**
     * 保存模型文件
     */
    public function saveToFile($code, $className, $directory = null)
    {
        if ($directory === null) {
            $directory = base_path('app/model');
        }
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        $filename = $directory . '/' . $className . '.php';
        
        return file_put_contents($filename, $code) !== false;
    }
}