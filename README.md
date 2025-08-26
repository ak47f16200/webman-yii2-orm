# Webman Yii2 ORM

将 Yii2 框架中经典的 ActiveRecord、Validator、DataProvider 和 Behaviors 功能移植到 Webman 框架中。

## 特性

- **ActiveRecord**: Yii2 风格的 ORM，支持关联查询、验证规则等
- **ActiveQuery**: 完全兼容 Yii2 的查询构建器，支持链式调用
- **Query Builder**: 流畅的数据库查询构建器
- **Validator**: 强大的数据验证系统，支持自定义规则
- **DataProvider**: 数据提供者，支持分页、排序、过滤
- **Behaviors**: 行为系统，支持 TimestampBehavior 等自动填充功能
- **Database**: 数据库抽象层，支持多种数据库
- **Multi-Database**: 完全支持多数据库连接，不同模型可连接不同数据库
- **Transaction**: 支持跨数据库事务操作
- **Model Validation**: 模型级别的数据验证
- **Yii2 Compatibility**: 100% 兼容 Yii2 的事务和数据库操作语法

## 安装

```bash
composer require luwc/webman-yii2-orm
```

### 配置

#### 1. 数据库连接配置

复制配置文件模板：
```bash
cp vendor/webman/yii2-orm/config/database.example.php config/database.php
```

编辑 `config/database.php`，配置您的数据库连接：
```php
return [
    'default' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'database' => 'your_database',
        'username' => 'your_username',
        'password' => 'your_password',
        'charset' => 'utf8mb4',
    ],
    // 可选：配置额外的数据库连接
    'log' => [
        'driver' => 'mysql',
        'host' => 'log-server',
        'database' => 'logs_database',
        'username' => 'log_user',
        'password' => 'log_password',
        'charset' => 'utf8mb4',
    ],
];
```

#### 2. Bootstrap配置

在 `config/bootstrap.php` 中添加初始化代码：
```php
// 引入Yii2 ORM初始化
require_once __DIR__ . '/../vendor/webman/yii2-orm/config/bootstrap.example.php';
```

或者手动初始化：
```php
use Webman\Yii2Orm\Database\Connection;

// 加载助手函数
require_once __DIR__ . '/../vendor/webman/yii2-orm/src/helpers/yii_compat.php';

// 配置数据库连接
$databaseConfig = config('database');
foreach ($databaseConfig as $name => $config) {
    Connection::addConnection($config, $name);
}
```

## 快速开始

### ActiveRecord 使用

```php
<?php
namespace app\model;

use Webman\Yii2Orm\ActiveRecord;
use Webman\Yii2Orm\Behaviors\TimestampBehavior;

class User extends ActiveRecord
{
    protected $table = 'users';
    
    public function rules()
    {
        return [
            [['username', 'email'], 'required'],
            ['email', 'email'],
            ['username', 'string', 'max' => 50],
        ];
    }
    
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
    
    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id');
    }
}
```

### ActiveQuery 强大查询

```php
// 完全兼容 Yii2 的查询语法
$users = User::find()
    ->where(['status' => 1])
    ->andWhere(['>', 'age', 18])
    ->orderBy('created_at DESC')
    ->limit(10)
    ->all();

// 复杂查询
$users = User::find()
    ->select(['id', 'username', 'email'])
    ->where(['status' => 1])
    ->andWhere(['like', 'username', 'john%'])
    ->leftJoin('user_profiles', 'users.id = user_profiles.user_id')
    ->asArray()
    ->all();

// 批量处理
foreach (User::find()->batch(100) as $users) {
    foreach ($users as $user) {
        // 处理每个用户
    }
}
```

### 数据验证

```php
use Webman\Yii2Orm\Validator;

$validator = new Validator([
    'email' => 'test@example.com',
    'age' => 25
], [
    'email' => 'required|email',
    'age' => 'required|integer|min:18'
]);

if ($validator->passes()) {
    // 验证通过
} else {
    $errors = $validator->errors();
}
```

### DataProvider 使用

```php
use Webman\Yii2Orm\ActiveDataProvider;

$dataProvider = new ActiveDataProvider([
    'query' => User::find(),
    'pagination' => [
        'pageSize' => 20,
    ],
    'sort' => [
        'attributes' => ['id', 'username', 'created_at']
    ]
]);

$users = $dataProvider->getModels();
$pagination = $dataProvider->getPagination();
```

### 数据库事务操作

#### 基础事务使用

```php
use Webman\Yii2Orm\Tools\DatabaseHelper;

// 方式1: 使用事务闭包（推荐）
DatabaseHelper::transaction(function() {
    $user = new User();
    $user->username = 'john_doe';
    $user->email = 'john@example.com';
    $user->save();
    
    $profile = new UserProfile();
    $profile->user_id = $user->id;
    $profile->nickname = 'John';
    $profile->save();
    
    // 如果这里抛出异常，所有操作都会回滚
    if ($user->id < 0) {
        throw new \Exception('用户ID无效');
    }
});
```

#### 手动控制事务

```php
// 方式2: 手动控制事务
$transaction = DatabaseHelper::beginTransaction();
try {
    // 创建用户
    $user = new User();
    $user->username = 'jane_doe';
    $user->email = 'jane@example.com';
    $user->save();
    
    // 创建用户资料
    $profile = new UserProfile();
    $profile->user_id = $user->id;
    $profile->nickname = 'Jane';
    $profile->avatar = '/uploads/avatar.jpg';
    $profile->save();
    
    // 创建用户设置
    $settings = new UserSettings();
    $settings->user_id = $user->id;
    $settings->theme = 'dark';
    $settings->language = 'zh-CN';
    $settings->save();
    
    // 提交事务
    DatabaseHelper::commit();
    echo "用户创建成功！";
} catch (\Exception $e) {
    // 回滚事务
    DatabaseHelper::rollback();
    echo "用户创建失败: " . $e->getMessage();
}
```

#### 复杂多表操作事务

```php
use Webman\Yii2Orm\Tools\DatabaseHelper;

// 复杂的多表操作
DatabaseHelper::transaction(function() {
    // 创建订单
    $order = new Order();
    $order->user_id = $userId;
    $order->total_amount = 299.99;
    $order->status = 'pending';
    $order->save();
    
    // 创建订单明细
    foreach ($items as $item) {
        $orderItem = new OrderItem();
        $orderItem->order_id = $order->id;
        $orderItem->product_id = $item['product_id'];
        $orderItem->quantity = $item['quantity'];
        $orderItem->price = $item['price'];
        $orderItem->save();
        
        // 减少库存
        $product = Product::findOne($item['product_id']);
        $product->stock -= $item['quantity'];
        $product->save();
    }
    
    // 更新用户积分
    $user = User::findOne($userId);
    $user->points += intval($order->total_amount);
    $user->save();
});
```

### 多数据库连接支持

完全兼容 Yii2 的多数据库连接功能，不同模型可以连接不同的数据库。

#### 配置多数据库连接

```php
use Webman\Yii2Bridge\Database\Connection;

// 在 config/database.php 或 bootstrap 中配置
class DatabaseConfig
{
    public static function setup()
    {
        // 默认数据库连接（主业务数据库）
        Connection::addConnection([
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'webman_main',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
        ], 'default');
        
        // 日志数据库连接
        Connection::addConnection([
            'driver' => 'mysql',
            'host' => 'log-server',
            'database' => 'webman_logs',
            'username' => 'log_user',
            'password' => 'log_password',
            'charset' => 'utf8mb4',
        ], 'log');
        
        // 统计数据库连接
        Connection::addConnection([
            'driver' => 'mysql',
            'host' => 'stats-server',
            'database' => 'webman_statistics',
            'username' => 'stats_user',
            'password' => 'stats_password',
            'charset' => 'utf8mb4',
        ], 'stats');
        
        // 缓存数据库连接（SQLite）
        Connection::addConnection([
            'driver' => 'sqlite',
            'database' => '/path/to/cache.sqlite',
        ], 'cache');
    }
}
```

#### 不同模型使用不同数据库

```php
// 用户模型 - 使用默认数据库
class User extends ActiveRecord
{
    protected $table = 'users';
    // 不指定 connectionName，使用默认连接
}

// 日志模型 - 使用日志数据库
class SystemLog extends ActiveRecord
{
    protected $table = 'system_logs';
    protected static $connectionName = 'log';  // 指定使用日志数据库
}

// 统计模型 - 使用统计数据库
class DailyStats extends ActiveRecord
{
    protected $table = 'daily_stats';
    protected static $connectionName = 'stats';  // 指定使用统计数据库
}

// 缓存模型 - 使用缓存数据库
class CacheData extends ActiveRecord
{
    protected $table = 'cache_data';
    protected static $connectionName = 'cache';  // 指定使用缓存数据库
}
```

#### 多数据库事务操作

```php
// 跨数据库的事务操作
try {
    // 主数据库事务
    $userTransaction = User::getDb()->beginTransaction();
    
    $user = new User();
    $user->username = 'test_user';
    $user->save();
    
    // 日志数据库事务
    $logTransaction = SystemLog::getDb()->beginTransaction();
    
    $log = new SystemLog();
    $log->level = 'info';
    $log->message = '用户注册成功';
    $log->save();
    
    // 统计数据库事务
    $statsTransaction = DailyStats::getDb()->beginTransaction();
    
    $stats = DailyStats::find()->where(['date' => date('Y-m-d')])->one();
    $stats->user_count += 1;
    $stats->save();
    
    // 提交所有事务
    $userTransaction->commit();
    $logTransaction->commit();
    $statsTransaction->commit();
    
} catch (\Exception $e) {
    // 回滚所有事务
    if (isset($userTransaction)) $userTransaction->rollback();
    if (isset($logTransaction)) $logTransaction->rollback();
    if (isset($statsTransaction)) $statsTransaction->rollback();
}
```

#### 原生SQL多数据库操作

```php
use Webman\Yii2Orm\Tools\DatabaseHelper;

// 在指定数据库连接上执行SQL
$users = DatabaseHelper::createCommand(
    'SELECT * FROM users WHERE status = :status',
    [':status' => 1],
    'default'  // 指定连接名称
)->queryAll();

// 在日志数据库上执行SQL
$logs = DatabaseHelper::createCommand(
    'SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 10',
    [],
    'log'  // 指定使用日志连接
)->queryAll();

// 在统计数据库上执行SQL
$statsCount = DatabaseHelper::createCommand(
    'SELECT COUNT(*) FROM daily_stats WHERE date >= :date',
    [':date' => date('Y-m-01')],
    'stats'  // 指定使用统计连接
)->queryScalar();
```

#### 完全兼容 Yii2 语法

```php
// 以下写法完全兼容，可以直接迁移
$transaction = Certification::getDb()->beginTransaction();
$transaction = \Yii::$app->db->beginTransaction();
$transaction = \Yii::$app->getDb()->beginTransaction();

// 原生SQL执行
\Yii::$app->db->createCommand($sql)->execute();
\Yii::$app->getDb()->createCommand($sql, $params)->queryAll();

// 模型事务
Model::getDb()->beginTransaction();
```

```php
/**
 * 订单创建示例 - 涉及多个表的复杂事务
 */
class OrderService
{
    public static function createOrder($orderData, $items)
    {
        return DatabaseHelper::transaction(function() use ($orderData, $items) {
            // 1. 创建订单主表
            $order = new Order();
            $order->user_id = $orderData['user_id'];
            $order->total_amount = $orderData['total_amount'];
            $order->status = 'pending';
            $order->save();
            
            $totalAmount = 0;
            
            // 2. 创建订单明细
            foreach ($items as $itemData) {
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $orderItem->product_id = $itemData['product_id'];
                $orderItem->quantity = $itemData['quantity'];
                $orderItem->price = $itemData['price'];
                $orderItem->save();
                
                $totalAmount += $itemData['quantity'] * $itemData['price'];
                
                // 3. 减少库存
                $product = Product::findOne($itemData['product_id']);
                if ($product->stock < $itemData['quantity']) {
                    throw new \Exception("商品 {$product->name} 库存不足");
                }
                
                $product->stock -= $itemData['quantity'];
                $product->save();
            }
            
            // 4. 验证总金额
            if (abs($totalAmount - $orderData['total_amount']) > 0.01) {
                throw new \Exception('订单金额计算错误');
            }
            
            // 5. 创建支付记录
            $payment = new Payment();
            $payment->order_id = $order->id;
            $payment->amount = $totalAmount;
            $payment->status = 'pending';
            $payment->save();
            
            // 6. 更新用户积分
            $user = User::findOne($orderData['user_id']);
            $user->points += intval($totalAmount / 10); // 每10元1积分
            $user->save();
            
            return $order;
        });
    }
}

// 使用示例
try {
    $order = OrderService::createOrder([
        'user_id' => 123,
        'total_amount' => 299.99
    ], [
        ['product_id' => 1, 'quantity' => 2, 'price' => 99.99],
        ['product_id' => 2, 'quantity' => 1, 'price' => 99.99]
    ]);
    
    echo "订单创建成功，订单号: {$order->id}";
} catch (\Exception $e) {
    echo "订单创建失败: " . $e->getMessage();
}
```

#### 兼容 Yii2 的数据库操作

```php
// 原有 Yii2 代码可以直接使用
$list = \Yii::$app->db->createCommand($sql)
    ->bindValue(':startDate', $params['start_date'])
    ->bindValue(':endDate', $params['end_date'])
    ->queryAll();

// 或者使用新的助手函数
$list = db_query($sql, [
    ':startDate' => $params['start_date'],
    ':endDate' => $params['end_date']
]);

// 执行非查询SQL
$affected = db_execute("UPDATE users SET status = ? WHERE id = ?", [1, 123]);
```

#### 完全兼容的 Yii2 事务写法 🔥

```php
// 方式1: Model::getDb()->beginTransaction() - 最常用的写法
$transaction = User::getDb()->beginTransaction();
try {
    $user = new User();
    $user->username = 'john';
    $user->save();
    
    $profile = new UserProfile();
    $profile->user_id = $user->id;
    $profile->save();
    
    $transaction->commit();
} catch (\Exception $e) {
    $transaction->rollback();
    throw $e;
}

// 方式2: \Yii::$app->db->beginTransaction()
$transaction = \Yii::$app->db->beginTransaction();
try {
    // 业务操作...
    $transaction->commit();
} catch (\Exception $e) {
    $transaction->rollback();
}

// 方式3: \Yii::$app->getDb()->beginTransaction()
$transaction = \Yii::$app->getDb()->beginTransaction();
try {
    // 复杂操作...
    \Yii::$app->getDb()->createCommand($sql)->execute();
    $transaction->commit();
} catch (\Exception $e) {
    $transaction->rollback();
}

// 方式4: 混合使用新老写法
$transaction = Certification::getDb()->beginTransaction();
try {
    // 使用原有 ActiveRecord
    $cert = new Certification();
    $cert->save();
    
    // 使用新的助手函数
    db_execute("UPDATE stats SET count = count + 1");
    
    $transaction->commit();
} catch (\Exception $e) {
    $transaction->rollback();
}
```

## 文档

- [ActiveRecord 使用指南](docs/activerecord.md)
- [Validator 使用指南](docs/validator.md)  
- [DataProvider 使用指南](docs/dataprovider.md)
- [Behaviors 行为系统指南](docs/behaviors.md)
- [多表事务操作示例](examples/TransactionExamples.php) 🔥 **新增**

## 无缝迁移

本包提供了完全兼容 Yii2 的 API，您的现有 Yii2 代码可以直接运行：

```php
// Yii2 中的代码
$users = User::find()
    ->where(['status' => 1])
    ->andWhere(['>', 'age', 18])
    ->orderBy('created_at DESC')
    ->limit(10)
    ->all();

// 在 Webman 中完全相同的代码 - 无需修改！
$users = User::find()
    ->where(['status' => 1])
    ->andWhere(['>', 'age', 18])
    ->orderBy('created_at DESC')
    ->limit(10)
    ->all();
```

🚀 **查看 CHANGELOG.md 了解如何在 5 分钟内将您的 Yii2 项目迁移到 Webman！**

## 许可证

MIT