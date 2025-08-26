# Behaviors 行为系统使用指南

## 简介

Behaviors（行为）是一种设计模式，允许您在不修改类本身的情况下为其添加功能。本包完全兼容 Yii2 的 behaviors 语法，让您可以轻松地为 ActiveRecord 模型添加各种行为功能。

## 基本概念

行为是一种特殊的组件，它可以：
- 监听模型的事件
- 在特定事件发生时执行相应的操作
- 为模型添加额外的方法和属性

## TimestampBehavior 时间戳行为

### 基本使用

```php
<?php
namespace App\Model;

use Webman\Yii2Bridge\ActiveRecord\ActiveRecord;
use Webman\Yii2Bridge\Behaviors\TimestampBehavior;

class User extends ActiveRecord
{
    protected $table = 'users';
    protected $fillable = ['username', 'email'];
    
    /**
     * 行为配置 - 完全兼容 Yii2 语法
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    # 创建之前
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created', 'modified'],
                    # 修改之前
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['modified']
                ],
                # 设置默认值
                'value' => time()
            ]
        ];
    }
}
```

### 配置选项

#### 1. 使用时间戳（默认）

```php
public function behaviors()
{
    return [
        'timestamp' => [
            'class' => TimestampBehavior::class,
            'attributes' => [
                ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
            ],
            'value' => time(), // Unix 时间戳
        ],
    ];
}
```

#### 2. 使用日期时间字符串

```php
public function behaviors()
{
    return [
        'timestamp' => [
            'class' => TimestampBehavior::class,
            'attributes' => [
                ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
            ],
            'value' => function() {
                return date('Y-m-d H:i:s');
            },
        ],
    ];
}
```

#### 3. 使用自定义字段名

```php
public function behaviors()
{
    return [
        'timestamp' => [
            'class' => TimestampBehavior::class,
            'attributes' => [
                ActiveRecord::EVENT_BEFORE_INSERT => ['create_time', 'update_time'],
                ActiveRecord::EVENT_BEFORE_UPDATE => ['update_time'],
            ],
            'value' => time(),
        ],
    ];
}
```

### 静态方法创建

```php
public function behaviors()
{
    return [
        // 默认配置（created_at, updated_at 字段）
        'timestamp' => TimestampBehavior::timestamp(),
        
        // 自定义字段
        'timestamp' => TimestampBehavior::timestamp([
            ActiveRecord::EVENT_BEFORE_INSERT => ['create_time', 'update_time'],
            ActiveRecord::EVENT_BEFORE_UPDATE => ['update_time'],
        ]),
        
        // 日期时间字符串格式
        'timestamp' => TimestampBehavior::datetime('Y-m-d H:i:s'),
        
        // 自定义格式和字段
        'timestamp' => TimestampBehavior::datetime('Y-m-d H:i:s', [
            ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
            ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
        ]),
    ];
}
```

## 自定义行为

### 创建自定义行为

```php
<?php
namespace App\Behaviors;

use Webman\Yii2Bridge\Behaviors\Behavior;
use Webman\Yii2Bridge\ActiveRecord\ActiveRecord;

class LogBehavior extends Behavior
{
    /**
     * @var string 日志文件路径
     */
    public $logFile = 'model.log';
    
    /**
     * 监听的事件
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'logInsert',
            ActiveRecord::EVENT_AFTER_UPDATE => 'logUpdate',
            ActiveRecord::EVENT_AFTER_DELETE => 'logDelete',
        ];
    }
    
    /**
     * 记录插入日志
     */
    public function logInsert($event)
    {
        $this->writeLog('INSERT', $this->owner->toArray());
    }
    
    /**
     * 记录更新日志
     */
    public function logUpdate($event)
    {
        $this->writeLog('UPDATE', $this->owner->toArray());
    }
    
    /**
     * 记录删除日志
     */
    public function logDelete($event)
    {
        $this->writeLog('DELETE', $this->owner->toArray());
    }
    
    /**
     * 写入日志
     */
    protected function writeLog($operation, $data)
    {
        $log = date('Y-m-d H:i:s') . " {$operation}: " . json_encode($data) . PHP_EOL;
        file_put_contents($this->logFile, $log, FILE_APPEND | LOCK_EX);
    }
}
```

### 使用自定义行为

```php
class User extends ActiveRecord
{
    public function behaviors()
    {
        return [
            'timestamp' => TimestampBehavior::timestamp(),
            'log' => [
                'class' => LogBehavior::class,
                'logFile' => '/path/to/user.log',
            ],
        ];
    }
}
```

## 内置行为示例

### 1. 软删除行为

```php
use Webman\Yii2Bridge\Behaviors\SoftDeleteBehavior;

class User extends ActiveRecord
{
    public function behaviors()
    {
        return [
            'softDelete' => [
                'class' => SoftDeleteBehavior::class,
                'deletedAtAttribute' => 'deleted_at',
                'deletedValue' => time(),
            ],
        ];
    }
}

// 使用
$user = User::findOne(1);
$user->delete(); // 软删除，设置 deleted_at 字段

// 恢复
$behavior = $user->getBehavior('softDelete');
$behavior->restore();

// 强制删除
$behavior->forceDelete();

// 检查是否已删除
$isDeleted = $behavior->isDeleted();
```

### 2. UUID 行为

```php
use Webman\Yii2Bridge\Behaviors\UuidBehavior;

class User extends ActiveRecord
{
    public function behaviors()
    {
        return [
            'uuid' => [
                'class' => UuidBehavior::class,
                'uuidAttribute' => 'uuid',
                'generateOnInsert' => true,
            ],
        ];
    }
}

// 创建用户时自动生成 UUID
$user = new User();
$user->username = 'john';
$user->save(); // uuid 字段自动填充
```

### 3. Slug 行为

```php
use Webman\Yii2Bridge\Behaviors\SlugBehavior;

class Article extends ActiveRecord
{
    public function behaviors()
    {
        return [
            'slug' => [
                'class' => SlugBehavior::class,
                'slugAttribute' => 'slug',
                'sourceAttribute' => 'title',
                'generateOnInsert' => true,
                'generateOnUpdate' => false,
            ],
        ];
    }
}

// 创建文章时自动生成 slug
$article = new Article();
$article->title = 'Hello World Article';
$article->save(); // slug 自动设置为 'hello-world-article'
```

## 行为管理

### 动态附加行为

```php
$user = new User();

// 附加行为
$user->attachBehavior('log', [
    'class' => LogBehavior::class,
    'logFile' => 'user.log',
]);

// 获取行为
$logBehavior = $user->getBehavior('log');

// 分离行为
$user->detachBehavior('log');

// 获取所有行为
$behaviors = $user->getBehaviors();
```

### 批量附加行为

```php
$user->attachBehaviors([
    'timestamp' => TimestampBehavior::timestamp(),
    'log' => [
        'class' => LogBehavior::class,
        'logFile' => 'user.log',
    ],
]);
```

## 事件系统

### 可用事件

- `EVENT_BEFORE_INSERT` - 插入前
- `EVENT_AFTER_INSERT` - 插入后
- `EVENT_BEFORE_UPDATE` - 更新前
- `EVENT_AFTER_UPDATE` - 更新后
- `EVENT_BEFORE_DELETE` - 删除前
- `EVENT_AFTER_DELETE` - 删除后
- `EVENT_BEFORE_SAVE` - 保存前（插入或更新）
- `EVENT_AFTER_SAVE` - 保存后（插入或更新）
- `EVENT_BEFORE_VALIDATE` - 验证前
- `EVENT_AFTER_VALIDATE` - 验证后

### 阻止默认行为

```php
class PreventDeleteBehavior extends Behavior
{
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_DELETE => 'preventDelete',
        ];
    }
    
    public function preventDelete($event)
    {
        // 阻止删除操作
        $event->preventDefault();
        
        // 可以抛出异常或记录日志
        throw new \Exception('此记录不允许删除');
    }
}
```

## 助手函数

```php
// 创建时间戳行为
$behavior = yii2_timestamp_behavior([
    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
]);

// 创建任意行为
$behavior = yii2_create_behavior(TimestampBehavior::class, [
    'value' => time(),
]);

// 或者
$behavior = yii2_create_behavior([
    'class' => TimestampBehavior::class,
    'value' => time(),
]);
```

## 最佳实践

### 1. 行为命名

使用描述性的名称来标识行为：

```php
public function behaviors()
{
    return [
        'timestamp' => TimestampBehavior::timestamp(),
        'softDelete' => SoftDeleteBehavior::class,
        'auditLog' => AuditLogBehavior::class,
    ];
}
```

### 2. 配置复用

为常用配置创建静态方法：

```php
class TimestampBehavior extends Behavior
{
    public static function chinese()
    {
        return new static([
            'attributes' => [
                ActiveRecord::EVENT_BEFORE_INSERT => ['创建时间', '更新时间'],
                ActiveRecord::EVENT_BEFORE_UPDATE => ['更新时间'],
            ],
            'value' => function() {
                return date('Y-m-d H:i:s');
            },
        ]);
    }
}
```

### 3. 条件性行为

```php
public function behaviors()
{
    $behaviors = [
        'timestamp' => TimestampBehavior::timestamp(),
    ];
    
    // 只在生产环境启用日志行为
    if (env('APP_ENV') === 'production') {
        $behaviors['log'] = LogBehavior::class;
    }
    
    return $behaviors;
}
```

### 4. 性能考虑

- 避免在行为中执行耗时操作
- 使用队列处理复杂的后台任务
- 合理使用事件，避免监听过多不必要的事件

现在您可以在 Webman 项目中使用完全兼容 Yii2 语法的 behaviors() 功能了！