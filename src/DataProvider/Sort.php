<?php

namespace Webman\Yii2Orm\DataProvider;

class Sort
{
    public $attributes = [];
    public $defaultOrder = [];
    public $sortParam = 'sort';
    public $params = [];
    
    const ASC = 'asc';
    const DESC = 'desc';
    
    protected $orders = [];
    
    public function __construct($config = [])
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        
        $this->loadFromRequest();
    }
    
    /**
     * 从请求中加载排序参数
     */
    protected function loadFromRequest()
    {
        $request = request();
        
        if ($request) {
            $sort = $request->get($this->sortParam, '');
            if ($sort) {
                $this->orders = $this->parseSortParam($sort);
            }
        }
        
        // 如果没有排序参数，使用默认排序
        if (empty($this->orders) && !empty($this->defaultOrder)) {
            $this->orders = $this->defaultOrder;
        }
    }
    
    /**
     * 解析排序参数
     */
    protected function parseSortParam($sortParam)
    {
        $orders = [];
        $sorts = explode(',', $sortParam);
        
        foreach ($sorts as $sort) {
            $sort = trim($sort);
            if (empty($sort)) {
                continue;
            }
            
            if (strpos($sort, '-') === 0) {
                $attribute = substr($sort, 1);
                $direction = self::DESC;
            } else {
                $attribute = $sort;
                $direction = self::ASC;
            }
            
            if ($this->hasAttribute($attribute)) {
                $orders[$attribute] = $direction;
            }
        }
        
        return $orders;
    }
    
    /**
     * 检查属性是否可排序
     */
    public function hasAttribute($attribute)
    {
        return isset($this->attributes[$attribute]) || in_array($attribute, $this->attributes);
    }
    
    /**
     * 获取排序条件
     */
    public function getOrders()
    {
        return $this->orders;
    }
    
    /**
     * 获取属性排序方向
     */
    public function getAttributeOrder($attribute)
    {
        return $this->orders[$attribute] ?? null;
    }
    
    /**
     * 创建排序链接
     */
    public function createUrl($attribute, $direction = null)
    {
        $orders = $this->orders;
        
        if ($direction === null) {
            // 如果当前是升序，则切换为降序，反之亦然
            if (isset($orders[$attribute])) {
                $direction = $orders[$attribute] === self::ASC ? self::DESC : self::ASC;
            } else {
                $direction = self::ASC;
            }
        }
        
        $orders[$attribute] = $direction;
        
        $sorts = [];
        foreach ($orders as $attr => $dir) {
            $sorts[] = ($dir === self::DESC ? '-' : '') . $attr;
        }
        
        $params = array_merge($this->params, [
            $this->sortParam => implode(',', $sorts)
        ]);
        
        $query = http_build_query($params);
        return request()->uri() . ($query ? '?' . $query : '');
    }
    
    /**
     * 获取排序链接
     */
    public function getLinks()
    {
        $links = [];
        
        foreach ($this->attributes as $attribute) {
            if (is_string($attribute)) {
                $attr = $attribute;
            } else {
                $attr = key($this->attributes);
            }
            
            $links[$attr] = [
                'asc' => $this->createUrl($attr, self::ASC),
                'desc' => $this->createUrl($attr, self::DESC),
                'current' => $this->getAttributeOrder($attr),
            ];
        }
        
        return $links;
    }
}