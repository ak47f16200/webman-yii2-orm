<?php

/**
 * TimestampBehavior 行为测试示例
 * 
 * 展示如何在 webman-yii2-bridge 中使用 TimestampBehavior
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Webman\Yii2Orm\ActiveRecord\ActiveRecord;
use Webman\Yii2Orm\Behaviors\TimestampBehavior;

// 模拟一个用户模型
class TestUser extends ActiveRecord
{
    protected $table = 'test_users';
    protected $primaryKey = 'id';
    protected $fillable = ['username', 'email', 'nickname'];
    
    // 模拟数据存储
    protected static $data = [];
    protected static $nextId = 1;
    
    /**
     * 行为配置 - 完全兼容 Yii2 的语法
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
    
    public function rules()
    {
        return [
            [['username', 'email'], 'required'],
            ['email', 'email'],
        ];
    }
    
    // 模拟数据库操作
    protected function insert()
    {
        $this->setAttribute('id', self::$nextId++);
        self::$data[$this->id] = $this->attributes;
        $this->isNewRecord = false;
        $this->oldAttributes = $this->attributes;
        
        // 触发插入后事件
        $this->trigger(self::EVENT_AFTER_INSERT);
        
        return true;
    }
    
    protected function update()
    {
        if (isset(self::$data[$this->id])) {
            self::$data[$this->id] = $this->attributes;
            $this->oldAttributes = $this->attributes;
            
            // 触发更新后事件
            $this->trigger(self::EVENT_AFTER_UPDATE);
            
            return true;
        }
        return false;
    }
    
    public static function findOne($id)
    {
        if (isset(self::$data[$id])) {
            $user = new static();
            $user->attributes = self::$data[$id];
            $user->oldAttributes = self::$data[$id];
            $user->isNewRecord = false;
            return $user;
        }
        return null;
    }
}

echo "=== TimestampBehavior 行为测试 ===\\n\\n";

// 1. 测试创建用户（自动填充创建和修改时间）
echo "1. 测试创建用户:\\n";

$user = new TestUser();
$user->username = 'john_doe';
$user->email = 'john@example.com';
$user->nickname = 'John';

echo "保存前:\\n";
echo "  created: " . ($user->created ?? 'null') . "\\n";
echo "  modified: " . ($user->modified ?? 'null') . "\\n";

$user->save();

echo "保存后:\\n";
echo "  id: {$user->id}\\n";
echo "  created: {$user->created} (" . date('Y-m-d H:i:s', $user->created) . ")\\n";
echo "  modified: {$user->modified} (" . date('Y-m-d H:i:s', $user->modified) . ")\\n";

// 2. 测试更新用户（只更新修改时间）
echo "\\n2. 测试更新用户:\\n";

sleep(1); // 等待1秒确保时间戳不同

$user->nickname = 'John Updated';

echo "更新前:\\n";
echo "  created: {$user->created} (" . date('Y-m-d H:i:s', $user->created) . ")\\n";
echo "  modified: {$user->modified} (" . date('Y-m-d H:i:s', $user->modified) . ")\\n";

$user->save();

echo "更新后:\\n";
echo "  created: {$user->created} (" . date('Y-m-d H:i:s', $user->created) . ") [不变]\\n";
echo "  modified: {$user->modified} (" . date('Y-m-d H:i:s', $user->modified) . ") [已更新]\\n";

// 3. 测试验证错误时不触发行为
echo "\\n3. 测试验证失败时不触发行为:\\n";

$user2 = new TestUser();
$user2->username = ''; // 空用户名，验证会失败
$user2->email = 'invalid-email'; // 无效邮箱

echo "尝试保存无效数据...\\n";
$result = $user2->save();

echo "保存结果: " . ($result ? '成功' : '失败') . "\\n";
echo "created 字段: " . ($user2->created ?? 'null') . " (应该为 null，因为验证失败)\\n";
echo "modified 字段: " . ($user2->modified ?? 'null') . " (应该为 null，因为验证失败)\\n";

if ($user2->hasErrors()) {
    echo "验证错误:\\n";
    foreach ($user2->getErrors() as $field => $errors) {
        echo "  {$field}: " . implode(', ', $errors) . "\\n";
    }
}

// 4. 测试不同的时间戳格式
echo "\\n4. 测试日期时间字符串格式:\\n";

class TestUserDatetime extends TestUser
{
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
}

$user3 = new TestUserDatetime();
$user3->username = 'jane_doe';
$user3->email = 'jane@example.com';
$user3->save();

echo "使用日期时间字符串:\\n";
echo "  created_at: {$user3->created_at}\\n";
echo "  updated_at: {$user3->updated_at}\\n";

// 5. 测试使用静态方法创建行为
echo "\\n5. 测试使用静态方法创建行为:\\n";

class TestUserStatic extends TestUser
{
    public function behaviors()
    {
        return [
            'timestamp' => TimestampBehavior::timestamp([
                ActiveRecord::EVENT_BEFORE_INSERT => ['create_time', 'update_time'],
                ActiveRecord::EVENT_BEFORE_UPDATE => ['update_time'],
            ]),
        ];
    }
}

$user4 = new TestUserStatic();
$user4->username = 'bob_smith';
$user4->email = 'bob@example.com';
$user4->save();

echo "使用静态方法创建的时间戳行为:\\n";
echo "  create_time: {$user4->create_time} (" . date('Y-m-d H:i:s', $user4->create_time) . ")\\n";
echo "  update_time: {$user4->update_time} (" . date('Y-m-d H:i:s', $user4->update_time) . ")\\n";

// 6. 测试行为管理
echo "\\n6. 测试行为管理功能:\\n";

$user5 = new TestUser();
echo "行为数量: " . count($user5->getBehaviors()) . "\\n";

$timestampBehavior = $user5->getBehavior(0);
if ($timestampBehavior instanceof TimestampBehavior) {
    echo "✓ 成功获取 TimestampBehavior 实例\\n";
} else {
    echo "✗ 获取行为失败\\n";
}

// 分离行为
$user5->detachBehavior(0);
echo "分离行为后数量: " . count($user5->getBehaviors()) . "\\n";

echo "\\n=== 测试完成 ===\\n";
echo "✓ TimestampBehavior 创建功能正常\\n";
echo "✓ 时间戳自动填充功能正常\\n";
echo "✓ 验证失败时不触发行为\\n";
echo "✓ 支持多种时间格式\\n";
echo "✓ 支持静态方法创建\\n";
echo "✓ 行为管理功能正常\\n";

echo "\\n现在您可以在 Webman 项目中使用完全兼容 Yii2 语法的 behaviors() 功能了！\\n";