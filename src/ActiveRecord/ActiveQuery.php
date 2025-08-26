<?php

namespace Webman\Yii2Orm\ActiveRecord;

use Webman\Yii2Orm\Database\Connection;
use Webman\Yii2Orm\Database\Query;

/**
 * ActiveQuery 类 - 完全兼容 Yii2 的 ActiveQuery
 * 
 * 提供 ActiveRecord 模型的查询功能
 */
class ActiveQuery
{
    /**
     * @var string ActiveRecord 模型类名
     */
    public $modelClass;
    
    /**
     * @var Query 查询构建器实例
     */
    protected $query;
    
    /**
     * @var array WITH 关联加载
     */
    public $with = [];
    
    /**
     * @var bool 是否返回数组而不是 ActiveRecord 实例
     */
    public $asArray = false;
    
    /**
     * @var array 索引字段
     */
    public $indexBy;
    
    public function __construct($modelClass = null)
    {
        $this->modelClass = $modelClass;
        $this->query = new Query($modelClass);
        
        if ($modelClass) {
            $model = new $modelClass();
            $this->from($model->tableName());
        }
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
    public function where($condition, $params = [])
    {
        if (is_array($condition)) {
            // 数组格式: ['id' => 1, 'status' => 'active']
            foreach ($condition as $column => $value) {
                $this->query->where($column, $value);
            }
        } elseif (is_string($condition)) {
            // 字符串格式: 'id = ? AND status = ?'
            $this->query->whereRaw($condition, $params);
        } else {
            $this->query->where($condition, $params);
        }
        return $this;
    }
    
    /**
     * AND WHERE 条件
     */
    public function andWhere($condition, $params = [])
    {
        if (is_array($condition)) {
            foreach ($condition as $column => $value) {
                $this->query->where($column, $value);
            }
        } elseif (is_string($condition)) {
            $this->query->whereRaw($condition, $params);
        }
        return $this;
    }
    
    /**
     * OR WHERE 条件
     */
    public function orWhere($condition, $params = [])
    {
        if (is_array($condition)) {
            foreach ($condition as $column => $value) {
                $this->query->orWhere($column, $value);
            }
        } elseif (is_string($condition)) {
            $this->query->orWhereRaw($condition, $params);
        }
        return $this;
    }
    
    /**
     * WHERE IN 条件
     */
    public function andWhereIn($column, $values)
    {
        $this->query->whereIn($column, $values);
        return $this;
    }
    
    /**
     * WHERE NOT IN 条件
     */
    public function andWhereNotIn($column, $values)
    {
        $this->query->whereNotIn($column, $values);
        return $this;
    }
    
    /**
     * WHERE BETWEEN 条件
     */
    public function andWhereBetween($column, $from, $to)
    {
        $this->query->whereBetween($column, [$from, $to]);
        return $this;
    }
    
    /**
     * WHERE LIKE 条件
     */
    public function andWhereLike($column, $value, $escape = true)
    {
        $this->query->where($column, 'like', $value);
        return $this;
    }
    
    /**
     * ORDER BY 排序
     */
    public function orderBy($columns)
    {
        if (is_string($columns)) {
            // 'name ASC, age DESC'
            $orders = explode(',', $columns);
            foreach ($orders as $order) {
                $parts = explode(' ', trim($order));
                $column = $parts[0];
                $direction = isset($parts[1]) ? strtolower($parts[1]) : 'asc';
                $this->query->orderBy($column, $direction);
            }
        } elseif (is_array($columns)) {
            // ['name' => SORT_ASC, 'age' => SORT_DESC]
            foreach ($columns as $column => $direction) {
                if (is_numeric($column)) {
                    // ['name ASC', 'age DESC']
                    $parts = explode(' ', trim($direction));
                    $col = $parts[0];
                    $dir = isset($parts[1]) ? strtolower($parts[1]) : 'asc';
                    $this->query->orderBy($col, $dir);
                } else {
                    // ['name' => SORT_ASC]
                    $dir = ($direction === SORT_DESC || $direction === 'desc') ? 'desc' : 'asc';
                    $this->query->orderBy($column, $dir);
                }
            }
        }
        return $this;
    }
    
    /**
     * GROUP BY 分组
     */
    public function groupBy($columns)
    {
        if (is_string($columns)) {
            $columns = explode(',', $columns);
        }
        $this->query->groupBy($columns);
        return $this;
    }
    
    /**
     * HAVING 条件
     */
    public function having($condition, $params = [])
    {
        $this->query->having($condition, null, $params);
        return $this;
    }
    
    /**
     * JOIN 查询
     */
    public function join($type, $table, $on = '', $params = [])
    {
        switch (strtolower($type)) {
            case 'inner':
                $this->query->join($table, $on);
                break;
            case 'left':
                $this->query->leftJoin($table, $on);
                break;
            case 'right':
                $this->query->rightJoin($table, $on);
                break;
            default:
                $this->query->join($table, $type); // type 作为 on 条件
        }
        return $this;
    }
    
    /**
     * INNER JOIN
     */
    public function innerJoin($table, $on = '', $params = [])
    {
        return $this->join('inner', $table, $on, $params);
    }
    
    /**
     * LEFT JOIN
     */
    public function leftJoin($table, $on = '', $params = [])
    {
        return $this->join('left', $table, $on, $params);
    }
    
    /**
     * RIGHT JOIN
     */
    public function rightJoin($table, $on = '', $params = [])
    {
        return $this->join('right', $table, $on, $params);
    }
    
    /**
     * LIMIT 限制
     */
    public function limit($limit)
    {
        $this->query->limit($limit);
        return $this;
    }
    
    /**
     * OFFSET 偏移
     */
    public function offset($offset)
    {
        $this->query->offset($offset);
        return $this;
    }
    
    /**
     * WITH 关联加载
     */
    public function with($relations)
    {
        if (is_string($relations)) {
            $relations = explode(',', $relations);
        }
        
        $this->with = array_merge($this->with, (array)$relations);
        return $this;
    }
    
    /**
     * 返回数组而不是 ActiveRecord 实例
     */
    public function asArray($value = true)
    {
        $this->asArray = $value;
        return $this;
    }
    
    /**
     * 设置结果索引字段
     */
    public function indexBy($column)
    {
        $this->indexBy = $column;
        return $this;
    }
    
    /**
     * 获取单个记录
     */
    public function one()
    {
        $this->query->limit(1);
        $result = $this->query->one();
        
        if (!$result) {
            return null;
        }
        
        if ($this->asArray) {
            return (array)$result;
        }
        
        if ($this->modelClass) {
            $model = $this->modelClass::fromArray((array)$result);
            return $this->loadRelations([$model])[0] ?? null;
        }
        
        return $result;
    }
    
    /**
     * 获取所有记录
     */
    public function all()
    {
        $results = $this->query->all();
        
        if ($this->asArray) {
            $data = [];
            foreach ($results as $result) {
                $item = (array)$result;
                if ($this->indexBy) {
                    $key = is_callable($this->indexBy) ? 
                        call_user_func($this->indexBy, $item) : $item[$this->indexBy];
                    $data[$key] = $item;
                } else {
                    $data[] = $item;
                }
            }
            return $data;
        }
        
        if (!$this->modelClass) {
            return $results;
        }
        
        $models = [];
        foreach ($results as $result) {
            $model = $this->modelClass::fromArray((array)$result);
            if ($this->indexBy) {
                $key = is_callable($this->indexBy) ? 
                    call_user_func($this->indexBy, $model) : $model->{$this->indexBy};
                $models[$key] = $model;
            } else {
                $models[] = $model;
            }
        }
        
        return $this->loadRelations($models);
    }
    
    /**
     * 统计记录数
     */
    public function count($q = '*')
    {
        return $this->query->count($q);
    }
    
    /**
     * 检查记录是否存在
     */
    public function exists()
    {
        return $this->query->exists();
    }
    
    /**
     * 获取单个字段值
     */
    public function scalar($column = null)
    {
        if ($column) {
            $this->query->select([$column]);
        }
        
        $result = $this->query->one();
        
        if ($result && $column) {
            return $result->$column ?? null;
        }
        
        return $result ? array_values((array)$result)[0] : null;
    }
    
    /**
     * 获取单列数据
     */
    public function column($column = null)
    {
        if ($column) {
            $this->query->select([$column]);
        }
        
        $results = $this->query->all();
        $data = [];
        
        foreach ($results as $result) {
            if ($column) {
                $data[] = $result->$column ?? null;
            } else {
                $values = array_values((array)$result);
                $data[] = $values[0] ?? null;
            }
        }
        
        return $data;
    }
    
    /**
     * 批量处理
     */
    public function batch($batchSize = 100)
    {
        // 返回生成器进行批量处理
        $offset = 0;
        
        while (true) {
            $query = clone $this;
            $batch = $query->limit($batchSize)->offset($offset)->all();
            
            if (empty($batch)) {
                break;
            }
            
            yield $batch;
            
            if (count($batch) < $batchSize) {
                break;
            }
            
            $offset += $batchSize;
        }
    }
    
    /**
     * 逐个处理
     */
    public function each($batchSize = 100)
    {
        foreach ($this->batch($batchSize) as $batch) {
            foreach ($batch as $model) {
                yield $model;
            }
        }
    }
    
    /**
     * 加载关联数据
     */
    protected function loadRelations($models)
    {
        if (empty($this->with) || empty($models)) {
            return $models;
        }
        
        // 简单实现，实际项目中需要更复杂的关联加载逻辑
        foreach ($this->with as $relation) {
            // 这里应该实现具体的关联加载逻辑
            // 暂时保持模型不变
        }
        
        return $models;
    }
    
    /**
     * 获取 SQL 语句
     */
    public function createCommand()
    {
        return $this->query;
    }
    
    /**
     * 魔术方法 - 委托到 Query 对象
     */
    public function __call($name, $arguments)
    {
        $result = call_user_func_array([$this->query, $name], $arguments);
        
        // 如果返回的是 Query 实例，返回当前 ActiveQuery
        if ($result instanceof Query) {
            return $this;
        }
        
        return $result;
    }
    
    /**
     * 克隆查询
     */
    public function __clone()
    {
        $this->query = clone $this->query;
    }
}