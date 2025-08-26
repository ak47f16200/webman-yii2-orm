<?php

namespace Webman\Yii2Orm\Validator\Rules;

use Webman\Yii2Orm\Validator\Rule;

class IdCardRule extends Rule
{
    public function passes($value, array $parameters = [])
    {
        // 18位身份证验证
        if (strlen($value) === 18) {
            return $this->validate18IdCard($value);
        }
        
        // 15位身份证验证
        if (strlen($value) === 15) {
            return $this->validate15IdCard($value);
        }
        
        return false;
    }
    
    protected function validate18IdCard($idCard)
    {
        if (!preg_match('/^\d{17}[\dXx]$/', $idCard)) {
            return false;
        }
        
        // 验证校验码
        $weights = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
        $checkCodes = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
        
        $sum = 0;
        for ($i = 0; $i < 17; $i++) {
            $sum += intval($idCard[$i]) * $weights[$i];
        }
        
        $checkCode = $checkCodes[$sum % 11];
        return strtoupper($idCard[17]) === $checkCode;
    }
    
    protected function validate15IdCard($idCard)
    {
        return preg_match('/^\d{15}$/', $idCard);
    }
    
    public function message($attribute, array $parameters = [])
    {
        return $attribute . ' 必须是有效的身份证号码';
    }
}