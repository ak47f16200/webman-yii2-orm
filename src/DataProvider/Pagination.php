<?php

namespace Webman\Yii2Orm\DataProvider;

class Pagination
{
    public $page = 1;
    public $pageSize = 20;
    public $totalCount = 0;
    public $pageParam = 'page';
    public $pageSizeParam = 'per_page';
    public $params = [];
    
    public function __construct($config = [])
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        
        // 从请求参数中获取分页信息
        $this->loadFromRequest();
    }
    
    /**
     * 从请求中加载分页参数
     */
    protected function loadFromRequest()
    {
        $request = request();
        
        if ($request) {
            $this->page = max(1, (int)$request->get($this->pageParam, 1));
            $pageSize = (int)$request->get($this->pageSizeParam, $this->pageSize);
            $this->pageSize = max(1, min(100, $pageSize)); // 限制每页最大100条
        }
    }
    
    /**
     * 获取偏移量
     */
    public function getOffset()
    {
        return ($this->page - 1) * $this->pageSize;
    }
    
    /**
     * 获取限制数量
     */
    public function getLimit()
    {
        return $this->pageSize;
    }
    
    /**
     * 获取总页数
     */
    public function getPageCount()
    {
        return $this->totalCount > 0 ? (int)ceil($this->totalCount / $this->pageSize) : 0;
    }
    
    /**
     * 是否有上一页
     */
    public function hasPrevPage()
    {
        return $this->page > 1;
    }
    
    /**
     * 是否有下一页
     */
    public function hasNextPage()
    {
        return $this->page < $this->getPageCount();
    }
    
    /**
     * 获取上一页页码
     */
    public function getPrevPage()
    {
        return $this->hasPrevPage() ? $this->page - 1 : null;
    }
    
    /**
     * 获取下一页页码
     */
    public function getNextPage()
    {
        return $this->hasNextPage() ? $this->page + 1 : null;
    }
    
    /**
     * 创建分页链接
     */
    public function createUrl($page, array $params = [])
    {
        $params = array_merge($this->params, $params);
        $params[$this->pageParam] = $page;
        $params[$this->pageSizeParam] = $this->pageSize;
        
        $query = http_build_query($params);
        return request()->uri() . ($query ? '?' . $query : '');
    }
    
    /**
     * 获取分页链接数组
     */
    public function getLinks()
    {
        $links = [];
        $pageCount = $this->getPageCount();
        
        // 首页
        if ($this->page > 1) {
            $links['first'] = $this->createUrl(1);
        }
        
        // 上一页
        if ($this->hasPrevPage()) {
            $links['prev'] = $this->createUrl($this->getPrevPage());
        }
        
        // 下一页
        if ($this->hasNextPage()) {
            $links['next'] = $this->createUrl($this->getNextPage());
        }
        
        // 末页
        if ($this->page < $pageCount) {
            $links['last'] = $this->createUrl($pageCount);
        }
        
        return $links;
    }
    
    /**
     * 转换为数组
     */
    public function toArray()
    {
        return [
            'current_page' => $this->page,
            'per_page' => $this->pageSize,
            'total' => $this->totalCount,
            'last_page' => $this->getPageCount(),
            'has_prev' => $this->hasPrevPage(),
            'has_next' => $this->hasNextPage(),
            'links' => $this->getLinks(),
        ];
    }
}