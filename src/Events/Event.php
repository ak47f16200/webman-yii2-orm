<?php

namespace Webman\Yii2Orm\Events;

class Event
{
    /**
     * @var string 事件名称
     */
    public $name;
    
    /**
     * @var object 触发事件的对象
     */
    public $sender;
    
    /**
     * @var mixed 事件数据
     */
    public $data;
    
    /**
     * @var bool 是否阻止默认行为
     */
    public $isValid = true;
    
    public function __construct($name = null, $sender = null, $data = null)
    {
        $this->name = $name;
        $this->sender = $sender;
        $this->data = $data;
    }
    
    /**
     * 阻止默认行为
     */
    public function preventDefault()
    {
        $this->isValid = false;
    }
}

trait EventTrait
{
    /**
     * @var array 事件处理器
     */
    private $_events = [];
    
    /**
     * 绑定事件处理器
     */
    public function on($name, $handler)
    {
        if (!isset($this->_events[$name])) {
            $this->_events[$name] = [];
        }
        
        $this->_events[$name][] = $handler;
    }
    
    /**
     * 解绑事件处理器
     */
    public function off($name, $handler = null)
    {
        if (!isset($this->_events[$name])) {
            return;
        }
        
        if ($handler === null) {
            unset($this->_events[$name]);
            return;
        }
        
        $handlers = $this->_events[$name];
        foreach ($handlers as $i => $h) {
            if ($h === $handler) {
                unset($this->_events[$name][$i]);
            }
        }
        
        if (empty($this->_events[$name])) {
            unset($this->_events[$name]);
        } else {
            $this->_events[$name] = array_values($this->_events[$name]);
        }
    }
    
    /**
     * 触发事件
     */
    public function trigger($name, $event = null)
    {
        if (!isset($this->_events[$name])) {
            return true;
        }
        
        if ($event === null) {
            $event = new Event($name, $this);
        } elseif (!($event instanceof Event)) {
            $event = new Event($name, $this, $event);
        }
        
        $event->name = $name;
        $event->sender = $this;
        
        foreach ($this->_events[$name] as $handler) {
            if (is_callable($handler)) {
                call_user_func($handler, $event);
            } elseif (is_array($handler) && count($handler) === 2) {
                call_user_func($handler, $event);
            }
            
            if (!$event->isValid) {
                break;
            }
        }
        
        return $event->isValid;
    }
    
    /**
     * 检查是否有事件处理器
     */
    public function hasEventHandlers($name)
    {
        return isset($this->_events[$name]) && !empty($this->_events[$name]);
    }
}