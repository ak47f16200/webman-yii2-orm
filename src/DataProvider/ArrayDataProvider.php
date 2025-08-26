<?php

namespace Webman\Yii2Orm\DataProvider;

class ArrayDataProvider extends BaseDataProvider
{
    public $allModels = [];
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
        $models = $this->allModels;
        
        // 应用排序
        if ($this->sort !== null) {
            $models = $this->applySort($models);
        }
        
        // 应用分页
        if ($this->pagination !== null) {
            $models = $this->applyPagination($models);
        }
        
        return $models;
    }
    
    /**
     * 准备键值
     */
    protected function prepareKeys($models)
    {
        $keys = [];
        
        if ($this->key !== null) {
            foreach ($models as $index => $model) {
                if (is_callable($this->key)) {
                    $keys[] = call_user_func($this->key, $model, $index);
                } elseif (is_string($this->key)) {
                    $keys[] = $model[$this->key] ?? $index;
                } else {
                    $keys[] = $index;
                }
            }
        } else {
            $keys = array_keys($models);
        }
        
        return $keys;
    }
    
    /**
     * 准备总数
     */
    protected function prepareTotalCount()
    {
        $count = count($this->allModels);
        
        // 更新分页的总数
        if ($this->pagination !== null) {
            $this->pagination->totalCount = $count;
        }
        
        return $count;
    }
    
    /**
     * 应用排序
     */
    protected function applySort($models)
    {
        $orders = $this->sort->getOrders();
        
        if (empty($orders)) {
            return $models;
        }
        
        usort($models, function ($a, $b) use ($orders) {
            foreach ($orders as $attribute => $direction) {
                $aValue = is_array($a) ? ($a[$attribute] ?? '') : $a->$attribute ?? '';
                $bValue = is_array($b) ? ($b[$attribute] ?? '') : $b->$attribute ?? '';
                
                if ($aValue === $bValue) {
                    continue;
                }
                
                $result = $aValue <=> $bValue;
                
                if ($direction === Sort::DESC) {
                    $result = -$result;
                }
                
                return $result;
            }
            
            return 0;
        });
        
        return $models;
    }
    
    /**
     * 应用分页
     */
    protected function applyPagination($models)
    {
        if ($this->pagination === null) {
            return $models;
        }
        
        $offset = $this->pagination->getOffset();
        $limit = $this->pagination->getLimit();
        
        return array_slice($models, $offset, $limit, true);
    }
    
    /**
     * 设置所有模型数据
     */
    public function setAllModels($models)
    {
        $this->allModels = $models;
        $this->refresh();
        return $this;
    }
}