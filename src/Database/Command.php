<?php

namespace Webman\Yii2Orm\Database;

use Webman\Yii2Orm\Database\Connection;

/**
 * Command 数据库命令类
 * 
 * 兼容 Yii2 的 Command 接口
 */
class Command
{
    /**
     * @var Connection 数据库连接
     */
    public $db;
    
    /**
     * @var string SQL 语句
     */
    public $sql;
    
    /**
     * @var array 参数绑定
     */
    public $params = [];
    
    /**
     * @var \Illuminate\Database\Query\Builder 查询构建器
     */
    protected $query;
    
    public function __construct($sql = null, $params = [])
    {
        $this->db = Connection::getInstance();
        $this->sql = $sql;
        $this->params = $params;
    }
    
    /**
     * 设置查询构建器
     */
    public function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }
    
    /**
     * 绑定参数
     */
    public function bindParam($name, &$value, $dataType = null, $length = null)
    {
        $this->params[$name] = $value;
        return $this;
    }
    
    /**
     * 绑定值
     */
    public function bindValue($name, $value, $dataType = null)
    {
        $this->params[$name] = $value;
        return $this;
    }
    
    /**
     * 绑定多个值
     */
    public function bindValues($values)
    {
        foreach ($values as $name => $value) {
            $this->bindValue($name, $value);
        }
        return $this;
    }
    
    /**
     * 执行查询，返回所有结果
     */
    public function queryAll()
    {
        if ($this->query) {
            return $this->query->get()->toArray();
        }
        
        return Connection::getInstance()->select($this->sql, $this->params);
    }
    
    /**
     * 执行查询，返回单个结果
     */
    public function queryOne()
    {
        if ($this->query) {
            $result = $this->query->first();
            return $result ? (array)$result : null;
        }
        
        $results = $this->db->select($this->sql, $this->params);
        return $results ? $results[0] : null;
    }
    
    /**
     * 执行查询，返回单个标量值
     */
    public function queryScalar()
    {
        $result = $this->queryOne();
        
        if ($result) {
            return is_array($result) ? array_values($result)[0] : $result;
        }
        
        return null;
    }
    
    /**
     * 执行查询，返回单列数据
     */
    public function queryColumn()
    {
        $results = $this->queryAll();
        $column = [];
        
        foreach ($results as $result) {
            $values = is_array($result) ? array_values($result) : [$result];
            $column[] = $values[0] ?? null;
        }
        
        return $column;
    }
    
    /**
     * 执行 SQL 语句
     */
    public function execute()
    {
        try {
            if ($this->query) {
                // 如果是查询构建器，需要根据类型执行不同操作
                return $this->query->get()->count();
            }
            
            return $this->db->statement($this->sql, $this->params);
        } catch (\Exception $e) {
            throw new \Exception('SQL execution failed: ' . $e->getMessage());
        }
    }
    
    /**
     * 插入数据
     */
    public function insert($table, $columns)
    {
        return $this->db->table($table)->insert($columns);
    }
    
    /**
     * 批量插入数据
     */
    public function batchInsert($table, $columns, $rows)
    {
        $data = [];
        foreach ($rows as $row) {
            if (is_array($columns)) {
                $data[] = array_combine($columns, $row);
            } else {
                $data[] = $row;
            }
        }
        
        return $this->db->table($table)->insert($data);
    }
    
    /**
     * 更新数据
     */
    public function update($table, $columns, $condition = '', $params = [])
    {
        $query = $this->db->table($table);
        
        if ($condition) {
            if (is_array($condition)) {
                foreach ($condition as $column => $value) {
                    $query->where($column, $value);
                }
            } else {
                $query->whereRaw($condition, $params);
            }
        }
        
        return $query->update($columns);
    }
    
    /**
     * 删除数据
     */
    public function delete($table, $condition = '', $params = [])
    {
        $query = $this->db->table($table);
        
        if ($condition) {
            if (is_array($condition)) {
                foreach ($condition as $column => $value) {
                    $query->where($column, $value);
                }
            } else {
                $query->whereRaw($condition, $params);
            }
        }
        
        return $query->delete();
    }
    
    /**
     * 创建表
     */
    public function createTable($table, $columns, $options = '')
    {
        // 这需要根据具体的数据库类型实现
        // 简化实现
        $sql = "CREATE TABLE {$table} (";
        $columnSql = [];
        
        foreach ($columns as $name => $type) {
            $columnSql[] = "{$name} {$type}";
        }
        
        $sql .= implode(', ', $columnSql) . ')';
        
        if ($options) {
            $sql .= ' ' . $options;
        }
        
        return $this->db->statement($sql);
    }
    
    /**
     * 删除表
     */
    public function dropTable($table)
    {
        return $this->db->statement("DROP TABLE IF EXISTS {$table}");
    }
    
    /**
     * 重命名表
     */
    public function renameTable($oldName, $newName)
    {
        return $this->db->statement("RENAME TABLE {$oldName} TO {$newName}");
    }
    
    /**
     * 添加列
     */
    public function addColumn($table, $column, $type)
    {
        return $this->db->statement("ALTER TABLE {$table} ADD COLUMN {$column} {$type}");
    }
    
    /**
     * 删除列
     */
    public function dropColumn($table, $column)
    {
        return $this->db->statement("ALTER TABLE {$table} DROP COLUMN {$column}");
    }
    
    /**
     * 重命名列
     */
    public function renameColumn($table, $oldName, $newName)
    {
        return $this->db->statement("ALTER TABLE {$table} CHANGE {$oldName} {$newName}");
    }
    
    /**
     * 创建索引
     */
    public function createIndex($name, $table, $columns, $unique = false)
    {
        $type = $unique ? 'UNIQUE INDEX' : 'INDEX';
        $columns = is_array($columns) ? implode(',', $columns) : $columns;
        
        return $this->db->statement("CREATE {$type} {$name} ON {$table} ({$columns})");
    }
    
    /**
     * 删除索引
     */
    public function dropIndex($name, $table)
    {
        return $this->db->statement("DROP INDEX {$name} ON {$table}");
    }
    
    /**
     * 获取最后插入的 ID
     */
    public function getLastInsertID()
    {
        return $this->db->getPdo()->lastInsertId();
    }
    
    /**
     * 准备 SQL 语句
     */
    public function prepare()
    {
        // 在这个简化实现中，我们不需要预处理
        return $this;
    }
    
    /**
     * 取消准备
     */
    public function cancel()
    {
        return $this;
    }
    
    /**
     * 获取 SQL 语句
     */
    public function getSql()
    {
        if ($this->query) {
            return $this->query->toSql();
        }
        
        return $this->sql;
    }
    
    /**
     * 获取原始 SQL（替换参数）
     */
    public function getRawSql()
    {
        $sql = $this->getSql();
        
        foreach ($this->params as $param => $value) {
            $sql = str_replace(':' . $param, $this->quoteValue($value), $sql);
        }
        
        return $sql;
    }
    
    /**
     * 引用值
     */
    protected function quoteValue($value)
    {
        if (is_string($value)) {
            return "'" . addslashes($value) . "'";
        } elseif (is_bool($value)) {
            return $value ? '1' : '0';
        } elseif (is_null($value)) {
            return 'NULL';
        }
        
        return (string)$value;
    }
    
    /**
     * 转换为字符串
     */
    public function __toString()
    {
        return $this->getRawSql();
    }
}