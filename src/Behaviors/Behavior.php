<?php

namespace Webman\Yii2Orm\Behaviors;

abstract class Behavior
{
    /**
     * @var \Webman\Yii2Orm\ActiveRecord\ActiveRecord 所属的模型实例
     */
    public $owner;
    
    /**
     * 附加到模型
     */
    public function attach($owner)
    {
        $this->owner = $owner;
        
        foreach ($this->events() as $event => $handler) {
            $owner->on($event, [$this, $handler]);
        }
    }
    
    /**
     * 从模型分离
     */
    public function detach()
    {
        if ($this->owner) {
            foreach ($this->events() as $event => $handler) {
                $this->owner->off($event, [$this, $handler]);
            }
            $this->owner = null;
        }
    }
    
    /**
     * 返回此行为监听的事件和对应的处理方法
     * 格式: ['eventName' => 'handlerMethod']
     */
    public function events()
    {
        return [];
    }
}