<?php

namespace Webman\Yii2Orm\DataProvider;

abstract class BaseDataProvider
{
    protected $models;
    protected $keys;
    protected $count;
    protected $totalCount;
    protected $pagination;
    protected $sort;
    
    public function __construct($config = [])
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    /**
     * 准备数据
     */
    abstract protected function prepareModels();
    
    /**
     * 准备键值
     */
    abstract protected function prepareKeys($models);
    
    /**
     * 准备总数
     */
    abstract protected function prepareTotalCount();
    
    /**
     * 获取模型数据
     */
    public function getModels()
    {
        if ($this->models === null) {
            $this->models = $this->prepareModels();
        }
        
        return $this->models;
    }
    
    /**
     * 获取键值
     */
    public function getKeys()
    {
        if ($this->keys === null) {
            $this->keys = $this->prepareKeys($this->getModels());
        }
        
        return $this->keys;
    }
    
    /**
     * 获取当前页数据数量
     */
    public function getCount()
    {
        if ($this->count === null) {
            $this->count = count($this->getModels());
        }
        
        return $this->count;
    }
    
    /**
     * 获取总数据量
     */
    public function getTotalCount()
    {
        if ($this->totalCount === null) {
            $this->totalCount = $this->prepareTotalCount();
        }
        
        return $this->totalCount;
    }
    
    /**
     * 获取分页信息
     */
    public function getPagination()
    {
        return $this->pagination;
    }
    
    /**
     * 设置分页
     */
    public function setPagination($pagination)
    {
        $this->pagination = $pagination;
        return $this;
    }
    
    /**
     * 获取排序信息
     */
    public function getSort()
    {
        return $this->sort;
    }
    
    /**
     * 设置排序
     */
    public function setSort($sort)
    {
        $this->sort = $sort;
        return $this;
    }
    
    /**
     * 刷新数据
     */
    public function refresh()
    {
        $this->models = null;
        $this->keys = null;
        $this->count = null;
        $this->totalCount = null;
    }
}