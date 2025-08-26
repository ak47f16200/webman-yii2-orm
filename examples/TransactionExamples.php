<?php

namespace Webman\Yii2Orm\Examples;

use Webman\Yii2Orm\ActiveRecord\ActiveRecord;
use Webman\Yii2Orm\Tools\DatabaseHelper;

/**
 * 事务操作示例
 * 
 * 本文件展示了在webman框架中使用事务处理的各种方式
 */

// 示例模型定义
class User extends ActiveRecord
{
    protected $table = 'users';
    protected $fillable = ['username', 'email', 'phone', 'status', 'points'];
}

class Order extends ActiveRecord
{
    protected $table = 'orders';
    protected $fillable = ['user_id', 'order_no', 'total_amount', 'status'];
}

class OrderItem extends ActiveRecord
{
    protected $table = 'order_items';
    protected $fillable = ['order_id', 'product_id', 'quantity', 'price'];
}

class Product extends ActiveRecord
{
    protected $table = 'products';
    protected $fillable = ['name', 'price', 'stock'];
}

/**
 * 事务操作服务类
 */
class TransactionService
{
    /**
     * 示例1：使用事务闭包（推荐方式）
     */
    public static function createOrderWithClosure($orderData, $items)
    {
        return DatabaseHelper::transaction(function() use ($orderData, $items) {
            // 创建订单
            $order = new Order();
            $order->user_id = $orderData['user_id'];
            $order->order_no = 'ORD' . date('YmdHis') . sprintf('%04d', mt_rand(1000, 9999));
            $order->total_amount = 0;
            $order->status = 'pending';
            $order->save();
            
            $totalAmount = 0;
            
            // 处理订单明细
            foreach ($items as $itemData) {
                $product = Product::findOne($itemData['product_id']);
                if (!$product || $product->stock < $itemData['quantity']) {
                    throw new \Exception('商品库存不足');
                }
                
                // 创建订单明细
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $orderItem->product_id = $itemData['product_id'];
                $orderItem->quantity = $itemData['quantity'];
                $orderItem->price = $product->price;
                $orderItem->save();
                
                // 减少库存
                $product->stock -= $itemData['quantity'];
                $product->save();
                
                $totalAmount += $orderItem->quantity * $orderItem->price;
            }
            
            // 更新订单总金额
            $order->total_amount = $totalAmount;
            $order->save();
            
            return $order;
        });
    }
    
    /**
     * 示例2：手动控制事务
     */
    public static function createUserWithManualTransaction($userData)
    {
        $transaction = DatabaseHelper::beginTransaction();
        
        try {
            // 创建用户
            $user = new User();
            $user->username = $userData['username'];
            $user->email = $userData['email'];
            $user->phone = $userData['phone'] ?? '';
            $user->status = 1;
            $user->points = 0;
            $user->save();
            
            // 模拟可能的错误
            if (empty($user->id)) {
                throw new \Exception('用户创建失败');
            }
            
            DatabaseHelper::commit();
            return $user;
            
        } catch (\Exception $e) {
            DatabaseHelper::rollback();
            throw $e;
        }
    }
    
    /**
     * 示例3：Yii2兼容写法
     */
    public static function yii2CompatibleTransaction($userData)
    {
        // 方式1：使用 \Yii::$app->db
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $user = new User();
            $user->setAttributes($userData);
            $user->save();
            
            $transaction->commit();
            return $user;
        } catch (\Exception $e) {
            $transaction->rollback();
            throw $e;
        }
    }
}

/**
 * 使用示例
 */
class TransactionExamples
{
    /**
     * 运行所有示例
     */
    public static function runAllExamples()
    {
        echo "=== Webman Yii2 Bridge 事务操作示例 ===\n\n";
        
        echo "1. 事务闭包示例：\n";
        try {
            $order = TransactionService::createOrderWithClosure([
                'user_id' => 1
            ], [
                ['product_id' => 1, 'quantity' => 2]
            ]);
            echo "✅ 订单创建成功，订单号: {$order->order_no}\n";
        } catch (\Exception $e) {
            echo "❌ 订单创建失败: " . $e->getMessage() . "\n";
        }
        
        echo "\n2. 手动事务控制示例：\n";
        try {
            $user = TransactionService::createUserWithManualTransaction([
                'username' => 'test_user',
                'email' => 'test@example.com'
            ]);
            echo "✅ 用户创建成功，用户ID: {$user->id}\n";
        } catch (\Exception $e) {
            echo "❌ 用户创建失败: " . $e->getMessage() . "\n";
        }
        
        echo "\n3. Yii2兼容写法示例：\n";
        try {
            $user = TransactionService::yii2CompatibleTransaction([
                'username' => 'yii2_user',
                'email' => 'yii2@example.com',
                'status' => 1,
                'points' => 0
            ]);
            echo "✅ Yii2兼容事务执行成功\n";
        } catch (\Exception $e) {
            echo "❌ Yii2兼容事务失败: " . $e->getMessage() . "\n";
        }
        
        echo "\n=== 示例运行完成 ===\n";
    }
}

/**
 * CLI运行示例
 */
if (php_sapi_name() === 'cli') {
    TransactionExamples::runAllExamples();
}