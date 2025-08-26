<?php

namespace Webman\Yii2Orm\Database;

use Webman\Yii2Orm\Tools\DatabaseHelper;

/**
 * 数据库事务类 - 兼容Yii2的Transaction类
 */
class Transaction
{
    protected $connectionName;
    
    public function __construct($connectionName = null)
    {
        $this->connectionName = $connectionName;
    }
    
    /**
     * 开始事务
     * 
     * @return Transaction
     */
    public function beginTransaction()
    {
        DatabaseHelper::beginTransaction($this->connectionName);
        return $this;
    }
    
    /**
     * 提交事务
     * 
     * @return bool
     */
    public function commit()
    {
        return DatabaseHelper::commit($this->connectionName);
    }
    
    /**
     * 回滚事务
     * 
     * @return bool
     */
    public function rollback()
    {
        return DatabaseHelper::rollback($this->connectionName);
    }
    
    /**
     * 执行事务闭包
     * 
     * @param callable $callback
     * @return mixed
     */
    public function transaction($callback)
    {
        return DatabaseHelper::transaction($callback, $this->connectionName);
    }
    
    /**
     * 创建数据库命令
     * 
     * @param string $sql
     * @param array $params
     * @return \Webman\Yii2Orm\Tools\DatabaseCommand
     */
    public function createCommand($sql = '', $params = [])
    {
        return DatabaseHelper::createCommand($sql, $params, $this->connectionName);
    }
    
    /**
     * 获取底层连接
     * 
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return DatabaseHelper::getConnection($this->connectionName);
    }
}

/**
 * 数据库连接类 - 兼容Yii2的Connection类
 */
class DatabaseConnection
{
    protected $connectionName;
    
    public function __construct($connectionName = null)
    {
        $this->connectionName = $connectionName;
    }
    
    /**
     * 开始事务
     * 
     * @return Transaction
     */
    public function beginTransaction()
    {
        DatabaseHelper::beginTransaction($this->connectionName);
        return new Transaction($this->connectionName);
    }
    
    /**
     * 提交事务
     * 
     * @return bool
     */
    public function commit()
    {
        return DatabaseHelper::commit($this->connectionName);
    }
    
    /**
     * 回滚事务
     * 
     * @return bool
     */
    public function rollback()
    {
        return DatabaseHelper::rollback($this->connectionName);
    }
    
    /**
     * 执行事务闭包
     * 
     * @param callable $callback
     * @return mixed
     */
    public function transaction($callback)
    {
        return DatabaseHelper::transaction($callback, $this->connectionName);
    }
    
    /**
     * 创建数据库命令
     * 
     * @param string $sql
     * @param array $params
     * @return \Webman\Yii2Orm\Tools\DatabaseCommand
     */
    public function createCommand($sql = '', $params = [])
    {
        return DatabaseHelper::createCommand($sql, $params, $this->connectionName);
    }
    
    /**
     * 获取底层连接
     * 
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return DatabaseHelper::getConnection($this->connectionName);
    }
    
    /**
     * 获取连接名称
     * 
     * @return string|null
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }
}