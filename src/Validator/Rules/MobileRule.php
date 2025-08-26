<?php

namespace Webman\Yii2Orm\Validator\Rules;

use Webman\Yii2Orm\Validator\Rule;

class MobileRule extends Rule
{
    public function passes($value, array $parameters = [])
    {
        return preg_match('/^1[3-9]\d{9}$/', $value);
    }
    
    public function message($attribute, array $parameters = [])
    {
        return $attribute . ' 必须是有效的手机号码';
    }
}