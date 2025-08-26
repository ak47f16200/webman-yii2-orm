<?php

namespace Webman\Yii2Orm\Tools;

use Webman\Yii2Orm\Database\Connection;
use Illuminate\Database\Connection as IlluminateConnection;

/**
 * 数据库助手类 - 兼容Yii2的数据库操作
 */
class DatabaseHelper
{
    /**
     * 创建数据库命令 - 兼容 Yii::$app->db->createCommand()
     * 
     * @param string $sql SQL语句
     * @param array $params 参数绑定
     * @param string|null $connectionName 连接名称
     * @return DatabaseCommand
     */
    public static function createCommand($sql = '', $params = [], $connectionName = null)
    {
        return new DatabaseCommand($sql, $params, $connectionName);
    }
    
    /**
     * 获取数据库连接实例
     * 
     * @param string|null $connectionName 连接名称
     * @return IlluminateConnection
     */
    public static function getConnection($connectionName = null)
    {
        return Connection::getInstance($connectionName);
    }
    
    /**
     * 开始事务
     * 
     * @param string|null $connectionName 连接名称
     * @return void
     */
    public static function beginTransaction($connectionName = null)
    {
        return self::getConnection($connectionName)->beginTransaction();
    }
    
    /**
     * 提交事务
     * 
     * @param string|null $connectionName 连接名称
     * @return void
     */
    public static function commit($connectionName = null)
    {
        return self::getConnection($connectionName)->commit();
    }
    
    /**
     * 回滚事务
     * 
     * @param string|null $connectionName 连接名称
     * @return void
     */
    public static function rollback($connectionName = null)
    {
        return self::getConnection($connectionName)->rollback();
    }
    
    /**
     * 执行事务
     * 
     * @param callable $callback
     * @param string|null $connectionName 连接名称
     * @return mixed
     */
    public static function transaction($callback, $connectionName = null)
    {
        return self::getConnection($connectionName)->transaction($callback);
    }
}

/**
 * 数据库命令类 - 兼容Yii2的Command类
 */
class DatabaseCommand
{
    protected $sql;
    protected $params = [];
    protected $connection;
    protected $connectionName;
    
    public function __construct($sql = '', $params = [], $connectionName = null)
    {
        $this->sql = $sql;
        $this->params = $params;
        $this->connectionName = $connectionName;
        $this->connection = DatabaseHelper::getConnection($connectionName);
    }
    
    /**
     * 绑定参数值
     * 
     * @param string $name 参数名
     * @param mixed $value 参数值
     * @return $this
     */
    public function bindValue($name, $value)
    {
        $this->params[$name] = $value;
        return $this;
    }
    
    /**
     * 绑定多个参数
     * 
     * @param array $params 参数数组
     * @return $this
     */
    public function bindValues($params)
    {
        $this->params = array_merge($this->params, $params);
        return $this;
    }
    
    /**
     * 执行查询并返回所有结果
     * 
     * @return array
     */
    public function queryAll()
    {
        return $this->connection->select($this->sql, $this->params);
    }
    
    /**
     * 执行查询并返回第一行结果
     * 
     * @return array|null
     */
    public function queryOne()
    {
        $results = $this->connection->select($this->sql, $this->params);
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * 执行查询并返回第一列的值
     * 
     * @return mixed
     */
    public function queryScalar()
    {
        $result = $this->queryOne();
        return $result ? array_values($result)[0] : null;
    }
    
    /**
     * 执行查询并返回第一列的所有值
     * 
     * @return array
     */
    public function queryColumn()
    {
        $results = $this->queryAll();
        if (empty($results)) {
            return [];
        }
        
        $column = [];
        $firstKey = array_keys($results[0])[0];
        foreach ($results as $row) {
            $column[] = $row[$firstKey];
        }
        
        return $column;
    }
    
    /**
     * 执行非查询SQL语句（INSERT, UPDATE, DELETE等）
     * 
     * @return int 影响的行数
     */
    public function execute()
    {
        // 判断SQL类型
        $sqlUpper = strtoupper(trim($this->sql));
        
        if (strpos($sqlUpper, 'INSERT') === 0) {
            $this->connection->statement($this->sql, $this->params);
            return $this->connection->getPdo()->lastInsertId();
        } elseif (strpos($sqlUpper, 'UPDATE') === 0 || strpos($sqlUpper, 'DELETE') === 0) {
            return $this->connection->affectingStatement($this->sql, $this->params);
        } else {
            // 其他类型的SQL（如CREATE TABLE等）
            return $this->connection->statement($this->sql, $this->params) ? 1 : 0;
        }
    }
    
    /**
     * 获取最后插入的ID
     * 
     * @return int
     */
    public function getLastInsertID()
    {
        return $this->connection->getPdo()->lastInsertId();
    }
    
    /**
     * 设置SQL语句
     * 
     * @param string $sql
     * @return $this
     */
    public function setSql($sql)
    {
        $this->sql = $sql;
        return $this;
    }
    
    /**
     * 获取SQL语句
     * 
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }
    
    /**
     * 插入数据 - 兼容Yii2的insert方法
     * 
     * @param string $table 表名
     * @param array $columns 列名和值的关联数组
     * @return $this
     */
    public function insert($table, $columns)
    {
        $columnNames = array_keys($columns);
        $placeholders = ':' . implode(', :', $columnNames);
        
        $this->sql = "INSERT INTO {$table} (" . implode(', ', $columnNames) . ") VALUES ({$placeholders})";
        $this->params = [];
        foreach ($columns as $name => $value) {
            $this->params[":$name"] = $value;
        }
        
        return $this;
    }
    
    /**
     * 更新数据 - 兼容Yii2的update方法
     * 
     * @param string $table 表名
     * @param array $columns 要更新的列
     * @param string|array $condition 条件
     * @param array $params 条件参数
     * @return $this
     */
    public function update($table, $columns, $condition = '', $params = [])
    {
        $sets = [];
        $this->params = [];
        
        foreach ($columns as $name => $value) {
            $sets[] = "{$name} = :{$name}";
            $this->params[":{$name}"] = $value;
        }
        
        $this->sql = "UPDATE {$table} SET " . implode(', ', $sets);
        
        if (!empty($condition)) {
            if (is_array($condition)) {
                $conditions = [];
                foreach ($condition as $key => $value) {
                    $conditions[] = "{$key} = :cond_{$key}";
                    $this->params[":cond_{$key}"] = $value;
                }
                $this->sql .= ' WHERE ' . implode(' AND ', $conditions);
            } else {
                $this->sql .= " WHERE {$condition}";
                $this->params = array_merge($this->params, $params);
            }
        }
        
        return $this;
    }
    
    /**
     * 删除数据 - 兼容Yii2的delete方法
     * 
     * @param string $table 表名
     * @param string|array $condition 条件
     * @param array $params 条件参数
     * @return $this
     */
    public function delete($table, $condition = '', $params = [])
    {
        $this->sql = "DELETE FROM {$table}";
        $this->params = [];
        
        if (!empty($condition)) {
            if (is_array($condition)) {
                $conditions = [];
                foreach ($condition as $key => $value) {
                    $conditions[] = "{$key} = :{$key}";
                    $this->params[":{$key}"] = $value;
                }
                $this->sql .= ' WHERE ' . implode(' AND ', $conditions);
            } else {
                $this->sql .= " WHERE {$condition}";
                $this->params = $params;
            }
        }
        
        return $this;
    }
    
    /**
     * 批量插入数据 - 兼容Yii2的batchInsert方法
     * 
     * @param string $table 表名
     * @param array $columns 列名数组
     * @param array $rows 数据行数组
     * @return $this
     */
    public function batchInsert($table, $columns, $rows)
    {
        if (empty($rows)) {
            return $this;
        }
        
        $placeholders = [];
        $this->params = [];
        $paramIndex = 0;
        
        foreach ($rows as $rowIndex => $row) {
            $rowPlaceholders = [];
            foreach ($columns as $colIndex => $column) {
                $paramKey = ":param{$paramIndex}";
                $rowPlaceholders[] = $paramKey;
                $this->params[$paramKey] = $row[$colIndex];
                $paramIndex++;
            }
            $placeholders[] = '(' . implode(', ', $rowPlaceholders) . ')';
        }
        
        $this->sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES " . implode(', ', $placeholders);
        
        return $this;
    }
}