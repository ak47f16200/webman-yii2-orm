# ActiveRecord 使用指南

## 简介

ActiveRecord 是一种设计模式，它将数据表中的行映射为对象，表中的字段映射为对象的属性。本包实现了类似 Yii2 的 ActiveRecord，让您可以在 Webman 框架中享受到熟悉的 ORM 体验。

## 基本使用

### 1. 创建模型

```php
<?php
namespace App\Model;

use Webman\Yii2Bridge\ActiveRecord\ActiveRecord;

class User extends ActiveRecord
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = ['username', 'email', 'nickname'];
    
    public function rules()
    {
        return [
            [['username', 'email'], 'required'],
            ['email', 'email'],
            ['username', 'string', 'max' => 50],
        ];
    }
}
```

### 2. 查询数据

#### 基础查询

```php
// 查找所有记录
$users = User::findAll();

// 根据主键查找
$user = User::findOne(1);

// 根据条件查找
$user = User::findOne(['username' => 'admin']);
$users = User::findAll(['status' => 1]);
```

#### 高级查询

```php
// 使用查询构建器
$users = User::find()
    ->where('status', 1)
    ->where('age', '>', 18)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->all();

// 条件查询
$users = User::find()
    ->whereIn('id', [1, 2, 3])
    ->whereNotNull('email')
    ->all();

// 关联查询
$users = User::find()
    ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
    ->select('users.*', 'user_profiles.avatar')
    ->all();
```

### 3. 创建和保存

```php
// 创建新记录
$user = new User();
$user->username = 'john';
$user->email = 'john@example.com';
$user->save();

// 批量赋值
$user = new User();
$user->setAttributes([
    'username' => 'jane',
    'email' => 'jane@example.com',
    'nickname' => 'Jane Doe'
]);
$user->save();

// 创建并保存
$user = new User([
    'username' => 'bob',
    'email' => 'bob@example.com'
]);
$user->save();
```

### 4. 更新数据

```php
// 查找并更新
$user = User::findOne(1);
$user->nickname = 'New Nickname';
$user->save();

// 批量更新属性
$user = User::findOne(1);
$user->setAttributes([
    'nickname' => 'Updated Name',
    'status' => 1
]);
$user->save();
```

### 5. 删除数据

```php
// 删除单个记录
$user = User::findOne(1);
$user->delete();

// 条件删除（通过查询构建器）
User::find()->where('status', 0)->delete();
```

## 验证

### 定义验证规则

```php
public function rules()
{
    return [
        // 必填验证
        [['username', 'email'], 'required'],
        
        // 字符串长度验证
        ['username', 'string', 'min' => 3, 'max' => 50],
        
        // 邮箱验证
        ['email', 'email'],
        
        // 数字验证
        ['age', 'integer', 'min' => 1, 'max' => 120],
        
        // 范围验证
        ['status', 'in', 'range' => [0, 1, 2]],
        
        // 正则验证
        ['mobile', 'regex', 'pattern' => '/^1[3-9]\d{9}$/'],
        
        // 自定义验证
        ['password', 'validatePassword'],
    ];
}
```

### 验证数据

```php
$user = new User();
$user->setAttributes($data);

if ($user->validate()) {
    $user->save();
} else {
    // 获取错误信息
    $errors = $user->getErrors();
    $firstError = $user->getFirstError();
}
```

## 关联关系

### Has One 关联

```php
class User extends ActiveRecord
{
    public function profile()
    {
        return $this->hasOne(UserProfile::class, 'user_id');
    }
}

// 使用关联
$user = User::findOne(1);
$profile = $user->profile();
```

### Has Many 关联

```php
class User extends ActiveRecord
{
    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id');
    }
}

// 使用关联
$user = User::findOne(1);
$posts = $user->posts();
```

### Belongs To 关联

```php
class Post extends ActiveRecord
{
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

// 使用关联
$post = Post::findOne(1);
$author = $post->author();
```

## 事件和钩子

### 保存前后的处理

```php
class User extends ActiveRecord
{
    public function save($validate = true)
    {
        // 保存前处理
        if ($this->isNewRecord()) {
            $this->created_at = date('Y-m-d H:i:s');
        }
        $this->updated_at = date('Y-m-d H:i:s');
        
        return parent::save($validate);
    }
}
```

## 实用方法

### 数据转换

```php
// 转换为数组
$userArray = $user->toArray();

// 转换为 JSON
$userJson = $user->toJson();
```

### 检查状态

```php
// 检查是否为新记录
if ($user->isNewRecord()) {
    // 新记录逻辑
}

// 检查是否有验证错误
if ($user->hasErrors()) {
    // 处理错误
}
```

## 最佳实践

1. **使用 fillable 和 guarded 属性**：控制可批量赋值的字段
2. **定义验证规则**：确保数据完整性
3. **使用属性标签**：提供友好的字段名称
4. **合理使用关联**：避免 N+1 查询问题
5. **事务处理**：对于复杂操作使用数据库事务

```php
use Webman\Yii2Bridge\Database\Connection;

Connection::transaction(function() {
    $user = new User();
    $user->setAttributes($userData);
    $user->save();
    
    $profile = new UserProfile();
    $profile->user_id = $user->id;
    $profile->setAttributes($profileData);
    $profile->save();
});
```