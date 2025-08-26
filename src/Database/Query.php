<?php

namespace Webman\Yii2Orm\Database;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Webman\Yii2Orm\Database\Connection;

class Query
{
    protected $query;
    protected $modelClass;
    
    public function __construct($modelClass = null)
    {
        $this->modelClass = $modelClass;
        $this->query = Connection::getInstance()->query();
    }
    
    /**
     * 设置查询的表
     */
    public function from($table)
    {
        $this->query->from($table);
        return $this;
    }
    
    /**
     * 选择字段
     */
    public function select($columns = ['*'])
    {
        $this->query->select($columns);
        return $this;
    }
    
    /**
     * WHERE 条件
     */
    public function where($column, $operator = null, $value = null)
    {
        $this->query->where($column, $operator, $value);
        return $this;
    }
    
    /**
     * OR WHERE 条件
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        $this->query->orWhere($column, $operator, $value);
        return $this;
    }
    
    /**
     * WHERE IN 条件
     */
    public function whereIn($column, $values)
    {
        $this->query->whereIn($column, $values);
        return $this;
    }
    
    /**
     * WHERE NOT IN 条件
     */
    public function whereNotIn($column, $values)
    {
        $this->query->whereNotIn($column, $values);
        return $this;
    }
    
    /**
     * WHERE NULL 条件
     */
    public function whereNull($column)
    {
        $this->query->whereNull($column);
        return $this;
    }
    
    /**
     * WHERE BETWEEN 条件
     */
    public function whereBetween($column, $values)
    {
        $this->query->whereBetween($column, $values);
        return $this;
    }
    
    /**
     * WHERE NOT BETWEEN 条件
     */
    public function whereNotBetween($column, $values)
    {
        $this->query->whereNotBetween($column, $values);
        return $this;
    }
    
    /**
     * 原始 WHERE 条件
     */
    public function whereRaw($sql, $bindings = [])
    {
        $this->query->whereRaw($sql, $bindings);
        return $this;
    }
    
    /**
     * 原始 OR WHERE 条件
     */
    public function orWhereRaw($sql, $bindings = [])
    {
        $this->query->orWhereRaw($sql, $bindings);
        return $this;
    }
    
    /**
     * WHERE EXISTS 条件
     */
    public function whereExists($callback)
    {
        $this->query->whereExists($callback);
        return $this;
    }
    
    /**
     * WHERE NOT EXISTS 条件
     */
    public function whereNotExists($callback)
    {
        $this->query->whereNotExists($callback);
        return $this;
    }
    
    /**
     * JOIN 查询
     */
    public function join($table, $first, $operator = null, $second = null)
    {
        $this->query->join($table, $first, $operator, $second);
        return $this;
    }
    
    /**
     * LEFT JOIN 查询
     */
    public function leftJoin($table, $first, $operator = null, $second = null)
    {
        $this->query->leftJoin($table, $first, $operator, $second);
        return $this;
    }
    
    /**
     * RIGHT JOIN 查询
     */
    public function rightJoin($table, $first, $operator = null, $second = null)
    {
        $this->query->rightJoin($table, $first, $operator, $second);
        return $this;
    }
    
    /**
     * ORDER BY 排序
     */
    public function orderBy($column, $direction = 'asc')
    {
        $this->query->orderBy($column, $direction);
        return $this;
    }
    
    /**
     * GROUP BY 分组
     */
    public function groupBy($columns)
    {
        $this->query->groupBy($columns);
        return $this;
    }
    
    /**
     * HAVING 条件
     */
    public function having($column, $operator = null, $value = null)
    {
        $this->query->having($column, $operator, $value);
        return $this;
    }
    
    /**
     * LIMIT 限制
     */
    public function limit($value)
    {
        $this->query->limit($value);
        return $this;
    }
    
    /**
     * OFFSET 偏移
     */
    public function offset($value)
    {
        $this->query->offset($value);
        return $this;
    }
    
    /**
     * 分页
     */
    public function paginate($page = 1, $perPage = 15)
    {
        $offset = ($page - 1) * $perPage;
        return $this->limit($perPage)->offset($offset);
    }
    
    /**
     * 获取单个记录
     */
    public function one()
    {
        $result = $this->query->first();
        
        if ($result && $this->modelClass) {
            return $this->modelClass::fromArray((array)$result);
        }
        
        return $result;
    }
    
    /**
     * 获取所有记录
     */
    public function all()
    {
        $results = $this->query->get();
        
        if ($this->modelClass) {
            return $results->map(function($item) {
                return $this->modelClass::fromArray((array)$item);
            });
        }
        
        return $results;
    }
    
    /**
     * 统计记录数
     */
    public function count($columns = '*')
    {
        return $this->query->count($columns);
    }
    
    /**
     * 求和
     */
    public function sum($column)
    {
        return $this->query->sum($column);
    }
    
    /**
     * 求平均值
     */
    public function avg($column)
    {
        return $this->query->avg($column);
    }
    
    /**
     * 求最大值
     */
    public function max($column)
    {
        return $this->query->max($column);
    }
    
    /**
     * 求最小值
     */
    public function min($column)
    {
        return $this->query->min($column);
    }
    
    /**
     * 分块处理
     */
    public function chunk($count, callable $callback)
    {
        $page = 1;
        
        while (true) {
            $results = $this->limit($count)->offset(($page - 1) * $count)->all();
            
            if (empty($results)) {
                break;
            }
            
            if ($callback($results, $page) === false) {
                break;
            }
            
            if (count($results) < $count) {
                break;
            }
            
            $page++;
        }
    }
    
    /**
     * 获取单个字段的值
     */
    public function value($column)
    {
        $result = $this->select([$column])->one();
        return $result ? $result->$column : null;
    }
    
    /**
     * 获取单列数据
     */
    public function pluck($column, $key = null)
    {
        $columns = $key ? [$column, $key] : [$column];
        $results = $this->select($columns)->all();
        
        $data = [];
        foreach ($results as $result) {
            $value = $result->$column;
            if ($key) {
                $data[$result->$key] = $value;
            } else {
                $data[] = $value;
            }
        }
        
        return $data;
    }
    
    /**
     * 检查记录是否存在
     */
    public function exists()
    {
        return $this->query->exists();
    }
    
    /**
     * 获取查询 SQL
     */
    public function toSql()
    {
        return $this->query->toSql();
    }
    
    /**
     * 获取原始查询构建器
     */
    public function getQuery(): QueryBuilder
    {
        return $this->query;
    }
}