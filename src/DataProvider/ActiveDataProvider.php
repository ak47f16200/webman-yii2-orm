<?php

namespace Webman\Yii2Orm\DataProvider;

use Webman\Yii2Orm\Database\Query;

class ActiveDataProvider extends BaseDataProvider
{
    public $query;
    public $key;
    
    public function __construct($config = [])
    {
        parent::__construct($config);
        
        // 如果没有设置分页，创建默认分页
        if ($this->pagination === null) {
            $this->pagination = new Pagination();
        }
        
        // 如果没有设置排序，创建默认排序
        if ($this->sort === null) {
            $this->sort = new Sort();
        }
    }
    
    /**
     * 准备模型数据
     */
    protected function prepareModels()
    {
        if (!$this->query instanceof Query) {
            return [];
        }
        
        $query = clone $this->query;
        
        // 应用排序
        if ($this->sort !== null) {
            $this->applySort($query);
        }
        
        // 应用分页
        if ($this->pagination !== null) {
            $this->applyPagination($query);
        }
        
        return $query->all()->toArray();
    }
    
    /**
     * 准备键值
     */
    protected function prepareKeys($models)
    {
        $keys = [];
        
        if ($this->key !== null) {
            foreach ($models as $model) {
                if (is_callable($this->key)) {
                    $keys[] = call_user_func($this->key, $model);
                } else {
                    $keys[] = $model[$this->key] ?? null;
                }
            }
        } else {
            // 默认使用数组索引
            $keys = array_keys($models);
        }
        
        return $keys;
    }
    
    /**
     * 准备总数
     */
    protected function prepareTotalCount()
    {
        if (!$this->query instanceof Query) {
            return 0;
        }
        
        $query = clone $this->query;
        $count = $query->count();
        
        // 更新分页的总数
        if ($this->pagination !== null) {
            $this->pagination->totalCount = $count;
        }
        
        return $count;
    }
    
    /**
     * 应用排序
     */
    protected function applySort($query)
    {
        $orders = $this->sort->getOrders();
        
        foreach ($orders as $attribute => $direction) {
            $query->orderBy($attribute, $direction);
        }
    }
    
    /**
     * 应用分页
     */
    protected function applyPagination($query)
    {
        // 设置分页的总数
        if ($this->pagination->totalCount === 0) {
            $countQuery = clone $this->query;
            $this->pagination->totalCount = $countQuery->count();
        }
        
        $query->limit($this->pagination->getLimit())
              ->offset($this->pagination->getOffset());
    }
    
    /**
     * 设置查询
     */
    public function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }
    
    /**
     * 获取查询
     */
    public function getQuery()
    {
        return $this->query;
    }
}