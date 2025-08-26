<?php

namespace Webman\Yii2Orm\Validator;

abstract class Rule
{
    /**
     * 验证规则
     * 
     * @param mixed $value 要验证的值
     * @param array $parameters 规则参数
     * @return bool
     */
    abstract public function passes($value, array $parameters = []);
    
    /**
     * 获取错误信息
     * 
     * @param string $attribute 属性名
     * @param array $parameters 规则参数
     * @return string
     */
    abstract public function message($attribute, array $parameters = []);
}