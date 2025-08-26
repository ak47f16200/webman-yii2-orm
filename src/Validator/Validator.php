<?php

namespace Webman\Yii2Orm\Validator;

class Validator
{
    protected $data = [];
    protected $rules = [];
    protected $errors = [];
    protected $messages = [];
    
    // 默认错误信息
    protected $defaultMessages = [
        'required' => ':attribute 不能为空',
        'string' => ':attribute 必须是字符串',
        'integer' => ':attribute 必须是整数',
        'numeric' => ':attribute 必须是数字',
        'email' => ':attribute 必须是有效的邮箱地址',
        'url' => ':attribute 必须是有效的URL',
        'min' => ':attribute 最小值为 :min',
        'max' => ':attribute 最大值为 :max',
        'between' => ':attribute 必须在 :min 和 :max 之间',
        'in' => ':attribute 必须在指定的值中',
        'not_in' => ':attribute 不能在指定的值中',
        'regex' => ':attribute 格式不正确',
        'unique' => ':attribute 已经存在',
        'exists' => ':attribute 不存在',
        'confirmed' => ':attribute 确认不匹配',
        'same' => ':attribute 必须和 :other 相同',
        'different' => ':attribute 必须和 :other 不同',
        'date' => ':attribute 必须是有效的日期',
        'date_format' => ':attribute 日期格式不正确',
        'before' => ':attribute 必须在 :date 之前',
        'after' => ':attribute 必须在 :date 之后',
    ];
    
    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $this->parseRules($rules);
        $this->messages = array_merge($this->defaultMessages, $messages);
    }
    
    /**
     * 解析验证规则
     */
    protected function parseRules(array $rules)
    {
        $parsed = [];
        
        foreach ($rules as $attribute => $rule) {
            if (is_string($rule)) {
                $parsed[$attribute] = explode('|', $rule);
            } elseif (is_array($rule)) {
                $parsed[$attribute] = $rule;
            }
        }
        
        return $parsed;
    }
    
    /**
     * 执行验证
     */
    public function validate()
    {
        $this->errors = [];
        
        foreach ($this->rules as $attribute => $rules) {
            foreach ($rules as $rule) {
                if (!$this->validateRule($attribute, $rule)) {
                    // 如果验证失败，跳过该属性的后续规则
                    break;
                }
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * 验证单个规则
     */
    protected function validateRule($attribute, $rule)
    {
        if (is_string($rule)) {
            $parts = explode(':', $rule, 2);
            $ruleName = $parts[0];
            $parameters = isset($parts[1]) ? explode(',', $parts[1]) : [];
        } else {
            $ruleName = $rule[0];
            $parameters = array_slice($rule, 1);
        }
        
        $value = $this->getValue($attribute);
        
        // 如果值为空且规则不是 required，则跳过验证
        if ($this->isEmpty($value) && $ruleName !== 'required') {
            return true;
        }
        
        $method = 'validate' . ucfirst($ruleName);
        
        if (method_exists($this, $method)) {
            return $this->$method($attribute, $value, $parameters);
        }
        
        return true;
    }
    
    /**
     * 获取属性值
     */
    protected function getValue($attribute)
    {
        return $this->data[$attribute] ?? null;
    }
    
    /**
     * 检查值是否为空
     */
    protected function isEmpty($value)
    {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }
    
    /**
     * 添加错误信息
     */
    protected function addError($attribute, $rule, array $parameters = [])
    {
        $message = $this->getMessage($attribute, $rule, $parameters);
        
        if (!isset($this->errors[$attribute])) {
            $this->errors[$attribute] = [];
        }
        
        $this->errors[$attribute][] = $message;
    }
    
    /**
     * 获取错误信息
     */
    protected function getMessage($attribute, $rule, array $parameters = [])
    {
        $message = $this->messages[$rule] ?? ':attribute 验证失败';
        
        $replacements = [
            ':attribute' => $attribute,
        ];
        
        foreach ($parameters as $key => $value) {
            $replacements[':' . $key] = $value;
        }
        
        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }
    
    /**
     * 验证必填
     */
    protected function validateRequired($attribute, $value, $parameters)
    {
        if ($this->isEmpty($value)) {
            $this->addError($attribute, 'required');
            return false;
        }
        
        return true;
    }
    
    /**
     * 验证字符串
     */
    protected function validateString($attribute, $value, $parameters)
    {
        if (!is_string($value)) {
            $this->addError($attribute, 'string');
            return false;
        }
        
        return true;
    }
    
    /**
     * 验证整数
     */
    protected function validateInteger($attribute, $value, $parameters)
    {
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            $this->addError($attribute, 'integer');
            return false;
        }
        
        return true;
    }
    
    /**
     * 验证数字
     */
    protected function validateNumeric($attribute, $value, $parameters)
    {
        if (!is_numeric($value)) {
            $this->addError($attribute, 'numeric');
            return false;
        }
        
        return true;
    }
    
    /**
     * 验证邮箱
     */
    protected function validateEmail($attribute, $value, $parameters)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($attribute, 'email');
            return false;
        }
        
        return true;
    }
    
    /**
     * 验证URL
     */
    protected function validateUrl($attribute, $value, $parameters)
    {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($attribute, 'url');
            return false;
        }
        
        return true;
    }
    
    /**
     * 验证最小值
     */
    protected function validateMin($attribute, $value, $parameters)
    {
        $min = $parameters[0] ?? 0;
        
        if (is_numeric($value)) {
            if ($value < $min) {
                $this->addError($attribute, 'min', ['min' => $min]);
                return false;
            }
        } elseif (is_string($value)) {
            if (strlen($value) < $min) {
                $this->addError($attribute, 'min', ['min' => $min]);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 验证最大值
     */
    protected function validateMax($attribute, $value, $parameters)
    {
        $max = $parameters[0] ?? 0;
        
        if (is_numeric($value)) {
            if ($value > $max) {
                $this->addError($attribute, 'max', ['max' => $max]);
                return false;
            }
        } elseif (is_string($value)) {
            if (strlen($value) > $max) {
                $this->addError($attribute, 'max', ['max' => $max]);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 验证范围
     */
    protected function validateBetween($attribute, $value, $parameters)
    {
        $min = $parameters[0] ?? 0;
        $max = $parameters[1] ?? 0;
        
        if (is_numeric($value)) {
            if ($value < $min || $value > $max) {
                $this->addError($attribute, 'between', ['min' => $min, 'max' => $max]);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 验证在指定值中
     */
    protected function validateIn($attribute, $value, $parameters)
    {
        if (!in_array($value, $parameters)) {
            $this->addError($attribute, 'in');
            return false;
        }
        
        return true;
    }
    
    /**
     * 验证不在指定值中
     */
    protected function validateNotIn($attribute, $value, $parameters)
    {
        if (in_array($value, $parameters)) {
            $this->addError($attribute, 'not_in');
            return false;
        }
        
        return true;
    }
    
    /**
     * 验证正则表达式
     */
    protected function validateRegex($attribute, $value, $parameters)
    {
        $pattern = $parameters[0] ?? '';
        
        if (!preg_match($pattern, $value)) {
            $this->addError($attribute, 'regex');
            return false;
        }
        
        return true;
    }
    
    /**
     * 验证确认字段
     */
    protected function validateConfirmed($attribute, $value, $parameters)
    {
        $confirmAttribute = $attribute . '_confirmation';
        $confirmValue = $this->getValue($confirmAttribute);
        
        if ($value !== $confirmValue) {
            $this->addError($attribute, 'confirmed');
            return false;
        }
        
        return true;
    }
    
    /**
     * 验证相同
     */
    protected function validateSame($attribute, $value, $parameters)
    {
        $other = $parameters[0] ?? '';
        $otherValue = $this->getValue($other);
        
        if ($value !== $otherValue) {
            $this->addError($attribute, 'same', ['other' => $other]);
            return false;
        }
        
        return true;
    }
    
    /**
     * 验证不同
     */
    protected function validateDifferent($attribute, $value, $parameters)
    {
        $other = $parameters[0] ?? '';
        $otherValue = $this->getValue($other);
        
        if ($value === $otherValue) {
            $this->addError($attribute, 'different', ['other' => $other]);
            return false;
        }
        
        return true;
    }
    
    /**
     * 验证日期
     */
    protected function validateDate($attribute, $value, $parameters)
    {
        if (!strtotime($value)) {
            $this->addError($attribute, 'date');
            return false;
        }
        
        return true;
    }
    
    /**
     * 验证日期格式
     */
    protected function validateDateFormat($attribute, $value, $parameters)
    {
        $format = $parameters[0] ?? 'Y-m-d';
        $date = \DateTime::createFromFormat($format, $value);
        
        if (!$date || $date->format($format) !== $value) {
            $this->addError($attribute, 'date_format');
            return false;
        }
        
        return true;
    }
    
    /**
     * 检查验证是否通过
     */
    public function passes()
    {
        return $this->validate();
    }
    
    /**
     * 检查验证是否失败
     */
    public function fails()
    {
        return !$this->validate();
    }
    
    /**
     * 获取错误信息
     */
    public function errors()
    {
        return $this->errors;
    }
    
    /**
     * 获取第一个错误
     */
    public function firstError($attribute = null)
    {
        if ($attribute !== null) {
            return $this->errors[$attribute][0] ?? null;
        }
        
        foreach ($this->errors as $errors) {
            return $errors[0] ?? null;
        }
        
        return null;
    }
}