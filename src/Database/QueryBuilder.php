<?php

namespace Webman\Yii2Orm\Database;

/**
 * QueryBuilder 查询构建器
 * 
 * 提供更高级的查询构建功能，兼容 Yii2 语法
 */
class QueryBuilder
{
    /**
     * @var Connection 数据库连接
     */
    protected $connection;
    
    public function __construct()
    {
        $this->connection = Connection::getInstance();
    }
    
    /**
     * 构建 SELECT 查询
     */
    public function select($query, $columns = ['*'])
    {
        if (is_string($columns)) {
            $columns = explode(',', str_replace(' ', '', $columns));
        }
        
        return $this->connection->table($query['from'])
            ->select($columns);
    }
    
    /**
     * 构建 INSERT 查询
     */
    public function insert($table, $columns, $values)
    {
        if (is_array($columns) && is_array($values)) {
            $data = array_combine($columns, $values);
            return $this->connection->table($table)->insert($data);
        }
        
        return $this->connection->table($table)->insert($columns);
    }
    
    /**
     * 构建批量 INSERT 查询
     */
    public function batchInsert($table, $columns, $rows)
    {
        $data = [];
        foreach ($rows as $row) {
            $data[] = array_combine($columns, $row);
        }
        
        return $this->connection->table($table)->insert($data);
    }
    
    /**
     * 构建 UPDATE 查询
     */
    public function update($table, $columns, $condition = '')
    {
        $query = $this->connection->table($table);
        
        if ($condition) {
            $query = $this->buildWhere($query, $condition);
        }
        
        return $query->update($columns);
    }
    
    /**
     * 构建 DELETE 查询
     */
    public function delete($table, $condition = '')
    {
        $query = $this->connection->table($table);
        
        if ($condition) {
            $query = $this->buildWhere($query, $condition);
        }
        
        return $query->delete();
    }
    
    /**
     * 构建 WHERE 条件
     */
    protected function buildWhere($query, $condition)
    {
        if (is_array($condition)) {
            foreach ($condition as $column => $value) {
                if (is_array($value)) {
                    $query->whereIn($column, $value);
                } else {
                    $query->where($column, $value);
                }
            }
        } elseif (is_string($condition)) {
            $query->whereRaw($condition);
        }
        
        return $query;
    }
    
    /**
     * 构建 JOIN 条件
     */
    public function buildJoin($query, $joins)
    {
        foreach ($joins as $join) {
            $type = $join[0] ?? 'inner';
            $table = $join[1] ?? '';
            $on = $join[2] ?? '';
            
            switch (strtolower($type)) {
                case 'inner':
                    $query->join($table, $on);
                    break;
                case 'left':
                    $query->leftJoin($table, $on);
                    break;
                case 'right':
                    $query->rightJoin($table, $on);
                    break;
            }
        }
        
        return $query;
    }
    
    /**
     * 构建 ORDER BY
     */
    public function buildOrderBy($query, $orderBy)
    {
        if (is_string($orderBy)) {
            $orders = explode(',', $orderBy);
            foreach ($orders as $order) {
                $parts = explode(' ', trim($order));
                $column = $parts[0];
                $direction = isset($parts[1]) ? $parts[1] : 'ASC';
                $query->orderBy($column, $direction);
            }
        } elseif (is_array($orderBy)) {
            foreach ($orderBy as $column => $direction) {
                $query->orderBy($column, $direction);
            }
        }
        
        return $query;
    }
    
    /**
     * 构建 GROUP BY
     */
    public function buildGroupBy($query, $groupBy)
    {
        if (is_string($groupBy)) {
            $groupBy = explode(',', str_replace(' ', '', $groupBy));
        }
        
        return $query->groupBy($groupBy);
    }
    
    /**
     * 构建 HAVING
     */
    public function buildHaving($query, $having)
    {
        if (is_string($having)) {
            $query->havingRaw($having);
        } elseif (is_array($having)) {
            foreach ($having as $column => $value) {
                $query->having($column, $value);
            }
        }
        
        return $query;
    }
    
    /**
     * 引用表名或字段名
     */
    public function quoteName($name)
    {
        if (strpos($name, '.') !== false) {
            $parts = explode('.', $name);
            return implode('.', array_map([$this, 'quoteSimpleName'], $parts));
        }
        
        return $this->quoteSimpleName($name);
    }
    
    /**
     * 引用简单名称
     */
    protected function quoteSimpleName($name)
    {
        if ($name === '*' || strpos($name, '`') !== false) {
            return $name;
        }
        
        return '`' . $name . '`';
    }
    
    /**
     * 引用值
     */
    public function quoteValue($value)
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
     * 构建 LIMIT 和 OFFSET
     */
    public function buildLimit($query, $limit, $offset = 0)
    {
        if ($limit !== null) {
            $query->limit($limit);
        }
        
        if ($offset > 0) {
            $query->offset($offset);
        }
        
        return $query;
    }
    
    /**
     * 获取表的列信息
     */
    public function getTableSchema($table)
    {
        try {
            // 使用 Laravel 的 Schema 方法
            return \Illuminate\Support\Facades\Schema::getColumnListing($table);
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * 检查表是否存在
     */
    public function tableExists($table)
    {
        try {
            return \Illuminate\Support\Facades\Schema::hasTable($table);
        } catch (\Exception $e) {
            return false;
        }
    }
}