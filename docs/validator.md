# Validator 使用指南

## 简介

Validator 提供了强大的数据验证功能，支持多种内置验证规则和自定义验证规则，让您可以轻松验证用户输入数据。

## 基本使用

### 1. 创建验证器

```php
use Webman\Yii2Bridge\Validator\Validator;

$data = [
    'username' => 'john',
    'email' => 'john@example.com',
    'age' => 25
];

$rules = [
    'username' => 'required|string|min:3|max:50',
    'email' => 'required|email',
    'age' => 'required|integer|min:18'
];

$validator = new Validator($data, $rules);
```

### 2. 执行验证

```php
if ($validator->passes()) {
    // 验证通过
    echo "验证成功";
} else {
    // 验证失败
    $errors = $validator->errors();
    print_r($errors);
}
```

### 3. 使用助手函数

```php
// 快速验证
$result = yii2_validate($data, $rules);

if ($result === true) {
    // 验证通过
} else {
    // $result 包含错误信息
    print_r($result);
}
```

## 验证规则

### 基础规则

#### required - 必填验证
```php
'username' => 'required'
```

#### string - 字符串验证
```php
'name' => 'string'
'name' => 'string|min:3|max:50'  // 带长度限制
```

#### integer - 整数验证
```php
'age' => 'integer'
'age' => 'integer|min:18|max:100'  // 带范围限制
```

#### numeric - 数字验证
```php
'price' => 'numeric'
'score' => 'numeric|min:0|max:100'
```

#### email - 邮箱验证
```php
'email' => 'email'
```

#### url - URL 验证
```php
'website' => 'url'
```

### 长度和范围验证

#### min - 最小值/长度
```php
'password' => 'min:6'     // 字符串最小长度
'age' => 'integer|min:18' // 数字最小值
```

#### max - 最大值/长度
```php
'username' => 'max:50'    // 字符串最大长度
'age' => 'integer|max:100' // 数字最大值
```

#### between - 范围验证
```php
'score' => 'numeric|between:0,100'
```

### 选项验证

#### in - 在指定值中
```php
'status' => 'in:0,1,2'
'gender' => 'in:male,female'
```

#### not_in - 不在指定值中
```php
'username' => 'not_in:admin,root,system'
```

### 正则表达式验证

#### regex - 正则验证
```php
'mobile' => 'regex:/^1[3-9]\d{9}$/'
'code' => 'regex:/^\d{6}$/'
```

### 字段比较验证

#### confirmed - 确认字段
```php
'password' => 'confirmed'  // 会检查 password_confirmation 字段
```

#### same - 相同验证
```php
'password_confirm' => 'same:password'
```

#### different - 不同验证
```php
'new_password' => 'different:old_password'
```

### 日期验证

#### date - 日期验证
```php
'birthday' => 'date'
```

#### date_format - 日期格式验证
```php
'start_date' => 'date_format:Y-m-d'
'created_at' => 'date_format:Y-m-d H:i:s'
```

## 自定义错误信息

### 全局自定义消息

```php
$messages = [
    'required' => ':attribute 不能为空',
    'email' => ':attribute 必须是有效的邮箱地址',
    'min' => ':attribute 最小长度为 :min 位',
];

$validator = new Validator($data, $rules, $messages);
```

### 字段特定消息

```php
$messages = [
    'username.required' => '用户名不能为空',
    'username.min' => '用户名最少需要3个字符',
    'email.email' => '请输入正确的邮箱格式',
];

$validator = new Validator($data, $rules, $messages);
```

## 自定义验证规则

### 1. 创建自定义规则类

```php
<?php
namespace App\Validator\Rules;

use Webman\Yii2Bridge\Validator\Rule;

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
```

### 2. 扩展验证器

```php
class CustomValidator extends Validator
{
    protected function validateMobile($attribute, $value, $parameters)
    {
        if (!preg_match('/^1[3-9]\d{9}$/', $value)) {
            $this->addError($attribute, 'mobile');
            return false;
        }
        return true;
    }
}
```

### 3. 使用自定义验证

```php
$rules = [
    'phone' => 'required|mobile'
];

$validator = new CustomValidator($data, $rules);
```

## 数组规则格式

除了字符串格式，还支持数组格式的规则：

```php
$rules = [
    'username' => ['required', 'string', ['min', 3], ['max', 50]],
    'email' => ['required', 'email'],
    'age' => ['required', 'integer', ['min', 18]],
];
```

## 条件验证

### 在控制器中使用

```php
class UserController
{
    public function store(Request $request)
    {
        $data = $request->post();
        
        $validator = new Validator($data, [
            'username' => 'required|string|min:3|max:50',
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'age' => 'integer|min:18',
        ]);
        
        if (!$validator->passes()) {
            return ResponseFormatter::validationError($validator->errors());
        }
        
        // 验证通过，处理业务逻辑
        // ...
    }
}
```

## 实用方法

### 获取错误信息

```php
// 获取所有错误
$errors = $validator->errors();

// 获取第一个错误
$firstError = $validator->firstError();

// 获取指定字段的第一个错误
$usernameError = $validator->firstError('username');
```

### 检查验证结果

```php
// 检查是否通过验证
if ($validator->passes()) {
    // 验证通过
}

// 检查是否验证失败
if ($validator->fails()) {
    // 验证失败
}
```

## 最佳实践

### 1. 在模型中定义验证规则

```php
class User extends ActiveRecord
{
    public function rules()
    {
        return [
            [['username', 'email'], 'required'],
            ['username', 'string', 'min' => 3, 'max' => 50],
            ['email', 'email'],
        ];
    }
}
```

### 2. 创建验证器工厂

```php
class ValidatorFactory
{
    public static function createUserValidator($data)
    {
        return new Validator($data, [
            'username' => 'required|string|min:3|max:50',
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);
    }
}
```

### 3. 统一的验证响应

```php
class BaseController
{
    protected function validateRequest(Request $request, array $rules)
    {
        $validator = new Validator($request->post(), $rules);
        
        if (!$validator->passes()) {
            return ResponseFormatter::validationError($validator->errors());
        }
        
        return null; // 验证通过
    }
}
```

### 4. 复杂验证示例

```php
// 用户注册验证
$rules = [
    'username' => 'required|string|min:3|max:50|regex:/^[a-zA-Z0-9_]+$/',
    'email' => 'required|email',
    'password' => 'required|string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
    'password_confirmation' => 'required|same:password',
    'age' => 'required|integer|min:18|max:100',
    'gender' => 'required|in:male,female',
    'mobile' => 'required|mobile',
    'agree_terms' => 'required|in:1',
];
```