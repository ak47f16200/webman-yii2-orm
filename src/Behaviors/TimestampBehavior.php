<?php

namespace Webman\Yii2Orm\Behaviors;

use Webman\Yii2Orm\ActiveRecord\ActiveRecord;

class TimestampBehavior extends Behavior
{
    /**
     * @var array 事件和对应要更新的属性
     */
    public $attributes = [
        ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
        ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
    ];
    
    /**
     * @var callable|string|null 用于生成时间戳的值
     */
    public $value;
    
    /**
     * @var string 时间戳格式，null 表示使用时间戳整数
     */
    public $timestampFormat;
    
    public function __construct($config = [])
    {
        if (isset($config['attributes'])) {
            $this->attributes = $config['attributes'];
        }
        
        if (isset($config['value'])) {
            $this->value = $config['value'];
        } else {
            // 默认使用当前时间戳
            $this->value = function() {
                return $this->timestampFormat ? date($this->timestampFormat) : time();
            };
        }
        
        if (isset($config['timestampFormat'])) {
            $this->timestampFormat = $config['timestampFormat'];
        }
    }
    
    /**
     * 返回监听的事件
     */
    public function events()
    {
        $events = [];
        
        foreach ($this->attributes as $event => $attributes) {
            $events[$event] = 'updateTimestamp';
        }
        
        return $events;
    }
    
    /**
     * 更新时间戳
     */
    public function updateTimestamp($event)
    {
        $attributes = isset($this->attributes[$event->name]) ? $this->attributes[$event->name] : [];
        
        if (empty($attributes)) {
            return;
        }
        
        $value = $this->getValue($event);
        
        foreach ($attributes as $attribute) {
            if ($this->owner->hasAttribute($attribute)) {
                $this->owner->setAttribute($attribute, $value);
            }
        }
    }
    
    /**
     * 获取时间戳值
     */
    protected function getValue($event)
    {
        if (is_callable($this->value)) {
            return call_user_func($this->value, $event);
        } elseif (is_string($this->value) && method_exists($this->owner, $this->value)) {
            return $this->owner->{$this->value}($event);
        }
        
        return $this->value;
    }
    
    /**
     * 创建一个返回当前时间戳的实例
     */
    public static function timestamp($attributes = null)
    {
        $config = [];
        
        if ($attributes !== null) {
            $config['attributes'] = $attributes;
        }
        
        $config['value'] = time();
        
        return new static($config);
    }
    
    /**
     * 创建一个返回当前日期时间字符串的实例
     */
    public static function datetime($format = 'Y-m-d H:i:s', $attributes = null)
    {
        $config = [
            'timestampFormat' => $format,
        ];
        
        if ($attributes !== null) {
            $config['attributes'] = $attributes;
        }
        
        $config['value'] = function() use ($format) {
            return date($format);
        };
        
        return new static($config);
    }
}