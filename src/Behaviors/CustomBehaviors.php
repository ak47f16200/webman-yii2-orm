<?php

namespace Webman\Yii2Orm\Behaviors;

use Webman\Yii2Orm\ActiveRecord\ActiveRecord;

/**
 * 软删除行为
 * 
 * 类似 Yii2 的软删除功能，不真正删除记录，而是标记为已删除
 */
class SoftDeleteBehavior extends Behavior
{
    /**
     * @var string 软删除标记字段名
     */
    public $deletedAtAttribute = 'deleted_at';
    
    /**
     * @var string 软删除标记值
     */
    public $deletedValue;
    
    /**
     * @var string 未删除标记值
     */
    public $notDeletedValue = null;
    
    public function __construct($config = [])
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        
        if ($this->deletedValue === null) {
            $this->deletedValue = time();
        }
    }
    
    /**
     * 返回监听的事件
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_DELETE => 'softDelete',
        ];
    }
    
    /**
     * 软删除处理
     */
    public function softDelete($event)
    {
        // 阻止真正的删除
        $event->preventDefault();
        
        // 设置软删除标记
        $value = is_callable($this->deletedValue) ? call_user_func($this->deletedValue) : $this->deletedValue;
        $this->owner->setAttribute($this->deletedAtAttribute, $value);
        
        // 保存记录
        $this->owner->save(false);
    }
    
    /**
     * 恢复软删除的记录
     */
    public function restore()
    {
        $this->owner->setAttribute($this->deletedAtAttribute, $this->notDeletedValue);
        return $this->owner->save(false);
    }
    
    /**
     * 强制删除（真正删除）
     */
    public function forceDelete()
    {
        // 临时分离行为以避免软删除
        $this->detach();
        $result = $this->owner->delete();
        $this->attach($this->owner);
        
        return $result;
    }
    
    /**
     * 检查是否已被软删除
     */
    public function isDeleted()
    {
        $value = $this->owner->getAttribute($this->deletedAtAttribute);
        return $value !== null && $value !== $this->notDeletedValue;
    }
}

/**
 * 自动填充UUID行为
 */
class UuidBehavior extends Behavior
{
    /**
     * @var string UUID字段名
     */
    public $uuidAttribute = 'uuid';
    
    /**
     * @var bool 是否在插入时自动生成
     */
    public $generateOnInsert = true;
    
    public function __construct($config = [])
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    /**
     * 返回监听的事件
     */
    public function events()
    {
        $events = [];
        
        if ($this->generateOnInsert) {
            $events[ActiveRecord::EVENT_BEFORE_INSERT] = 'generateUuid';
        }
        
        return $events;
    }
    
    /**
     * 生成UUID
     */
    public function generateUuid($event)
    {
        if (empty($this->owner->getAttribute($this->uuidAttribute))) {
            $this->owner->setAttribute($this->uuidAttribute, $this->createUuid());
        }
    }
    
    /**
     * 创建UUID
     */
    protected function createUuid()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

/**
 * 自动生成Slug行为
 */
class SlugBehavior extends Behavior
{
    /**
     * @var string Slug字段名
     */
    public $slugAttribute = 'slug';
    
    /**
     * @var string 用于生成Slug的源字段
     */
    public $sourceAttribute = 'title';
    
    /**
     * @var bool 是否在插入时生成
     */
    public $generateOnInsert = true;
    
    /**
     * @var bool 是否在更新时重新生成
     */
    public $generateOnUpdate = false;
    
    public function __construct($config = [])
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    /**
     * 返回监听的事件
     */
    public function events()
    {
        $events = [];
        
        if ($this->generateOnInsert) {
            $events[ActiveRecord::EVENT_BEFORE_INSERT] = 'generateSlug';
        }
        
        if ($this->generateOnUpdate) {
            $events[ActiveRecord::EVENT_BEFORE_UPDATE] = 'generateSlug';
        }
        
        return $events;
    }
    
    /**
     * 生成Slug
     */
    public function generateSlug($event)
    {
        $source = $this->owner->getAttribute($this->sourceAttribute);
        
        if (!empty($source)) {
            $slug = $this->createSlug($source);
            $this->owner->setAttribute($this->slugAttribute, $slug);
        }
    }
    
    /**
     * 创建Slug
     */
    protected function createSlug($text)
    {
        // 转换为小写
        $text = strtolower($text);
        
        // 替换非字母数字字符为连字符
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        
        // 去掉首尾连字符
        $text = trim($text, '-');
        
        return $text;
    }
}