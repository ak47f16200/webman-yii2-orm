<?php

if (!function_exists('yii2_db')) {
    /**
     * 获取数据库连接实例
     */
    function yii2_db($connection = 'default')
    {
        return \Webman\Yii2Orm\Database\Connection::getInstance();
    }
}

if (!function_exists('yii2_validator')) {
    /**
     * 创建验证器实例
     */
    function yii2_validator(array $data, array $rules, array $messages = [])
    {
        return new \Webman\Yii2Orm\Validator\Validator($data, $rules, $messages);
    }
}

if (!function_exists('yii2_validate')) {
    /**
     * 快速验证数据
     */
    function yii2_validate(array $data, array $rules, array $messages = [])
    {
        $validator = yii2_validator($data, $rules, $messages);
        return $validator->validate() ? true : $validator->errors();
    }
}

if (!function_exists('yii2_paginate')) {
    /**
     * 创建分页实例
     */
    function yii2_paginate(array $config = [])
    {
        return new \Webman\Yii2Orm\DataProvider\Pagination($config);
    }
}

if (!function_exists('yii2_sort')) {
    /**
     * 创建排序实例
     */
    function yii2_sort(array $config = [])
    {
        return new \Webman\Yii2Orm\DataProvider\Sort($config);
    }
}

if (!function_exists('yii2_data_provider')) {
    /**
     * 创建数据提供者
     */
    function yii2_data_provider($query, array $config = [])
    {
        $config['query'] = $query;
        return new \Webman\Yii2Orm\DataProvider\ActiveDataProvider($config);
    }
}

if (!function_exists('yii2_array_provider')) {
    /**
     * 创建数组数据提供者
     */
    function yii2_array_provider(array $data, array $config = [])
    {
        $config['allModels'] = $data;
        return new \Webman\Yii2Orm\DataProvider\ArrayDataProvider($config);
    }
}

if (!function_exists('yii2_timestamp_behavior')) {
    /**
     * 创建时间戳行为
     */
    function yii2_timestamp_behavior(array $attributes = null, $value = null)
    {
        $config = [];
        
        if ($attributes !== null) {
            $config['attributes'] = $attributes;
        }
        
        if ($value !== null) {
            $config['value'] = $value;
        }
        
        return new \Webman\Yii2Orm\Behaviors\TimestampBehavior($config);
    }
}

if (!function_exists('yii2_create_behavior')) {
    /**
     * 创建行为实例
     */
    function yii2_create_behavior($class, array $config = [])
    {
        if (is_array($class)) {
            $config = array_merge($class, $config);
            $class = $config['class'];
            unset($config['class']);
        }
        
        return new $class($config);
    }
}