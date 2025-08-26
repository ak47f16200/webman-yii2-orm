<?php

namespace App\Services\Statistics;

use Webman\Yii2Orm\ActiveRecord\ActiveRecord;
use Webman\Yii2Orm\Tools\DatabaseHelper;

// 示例模型类 - 在实际使用中，这些应该在 App\Model 命名空间下
class Users extends ActiveRecord {
    protected $table = 'yjh_users';
}

class Mall extends ActiveRecord {
    protected $table = 'yjh_mall';
}

class MallGoods extends ActiveRecord {
    protected $table = 'yjh_mall_goods';
    
    public function mall() {
        return $this->belongsTo(Mall::class, 'shop_id');
    }
}

/**
 * 数据统计日报 - Webman适配版本
 * 
 * 这个类展示了如何将原有的Yii2 DayDataService无缝迁移到webman框架中
 */
class DayDataService
{
    /**
     * 当前总量看板
     * 
     * 原有代码可以完全保持不变，因为我们的bridge包提供了完整的Yii2兼容API
     * 
     * @return array
     */
    public static function main()
    {
        // 这些调用在webman-yii2-bridge中都可以正常工作
        $dealer_num = Users::find()->where(['is_dealer' => 1])->count();
        $mall_num = Mall::find()->where(['status' => 1])->count();
        $mall_free_num = Mall::find()->where(['is_pay' => 0, 'status' => 1])->count();
        $mall_no_free_num = Mall::find()->where(['is_pay' => 1, 'status' => 1])->count();
        $product_num = MallGoods::find()->alias('g')->joinWith(['mall m'])->where(['m.status' => 1, 'g.is_check' => 1])->count();
        $product_free_num = MallGoods::find()->alias('g')->joinWith(['mall m'])->where(['m.status' => 1, 'g.is_check' => 1, 'is_pay' => 0])->count();
        $product_no_free_num = MallGoods::find()->alias('g')->joinWith(['mall m'])->where(['m.status' => 1, 'g.is_check' => 1, 'is_pay' => 1])->count();
        
        return [
            'dealer_num' => $dealer_num ?: '0',
            'mall_num' => $mall_num ?: '0',
            'mall_free_num' => $mall_free_num ?: '0',
            'mall_no_free_num' => $mall_no_free_num ?: '0',
            'product_num' => $product_num ?: '0',
            'product_free_num' => $product_free_num ?: '0',
            'product_no_free_num' => $product_no_free_num ?: '0',
        ];
    }

    /**
     * 活跃用户看板 - 完全兼容的版本
     * 
     * 原有的\Yii::$app->db->createCommand()调用现在可以直接工作
     * 
     * @param array $params
     * @return array
     */
    public static function user($params = [])
    {
        $page = ($params['page']) ?: 1;
        $limit = ($params['limit']) ?: 10;
        if ($limit != -1) {
            $p = ($page - 1) * $limit;
            $p_str = " limit " . $p . ',' . $limit;
        }
        if (empty($params['start_date'])) {
            $params['start_date'] = date('Y-m-d', strtotime('-1 days'));
        }
        if (empty($params['end_date'])) {
            $params['end_date'] = date('Y-m-d', strtotime('-1 days'));
        }

        $log_query = "select l.date,l.user_id,ifnull(f.id,0) as staff_id,if(u.phone!='' and f.id is null,1,0) phone from `yjh_buried_point_day_log` l 
        left join yjh_users_staff f on f.userid=l.user_id and f.ht_staff=1 
        left join yjh_users u on u.id=l.user_id  where l.date>='" . $params['start_date'] . "' and l.date<='" . $params['end_date'] . "' group by l.date,l.user_id";
        
        $log = "select l.date,count(l.user_id) uv,sum(if(l.staff_id>0,1,0)) staff ,sum(l.phone) phone from (" . $log_query . ") l group by l.date";

        $user = "select count(u.id) new, sum(if((case when is_dealer=1 then 1 when is_mall=1 then 1 when is_other=1 then 1 end)=1 and f.id is null,1,0)) as cert,
       FROM_UNIXTIME(u.created,'%Y-%m-%d') created,
        sum(if(authorized_phone!='',1,0)) authorized ,sum(if(is_dealer=1 and phone!='',1,0)) complete from yjh_users u 
        left join  yjh_users_staff f on f.userid=u.id and f.ht_staff=1 
        where u.created>=" . strtotime($params['start_date']) . " and u.created<=" . strtotime($params['end_date'] . ' 23:59:59') . " 
         group by FROM_UNIXTIME(created,'%Y-%m-%d')";

        $sql = "
        SELECT date_series.date AS date,
        ifnull(l.uv,0) uv,ifnull(l.staff,0) staff,ifnull(l.phone,0) phone,
        ifnull(u.new,0) new ,ifnull(u.cert,0) cert,ifnull(u.authorized,0) authorized,ifnull(u.complete,0) complete,
        0 as wechat_num,0 as wechat_no_cert
        FROM (
            SELECT DATE_ADD(:startDate, INTERVAL t4.i*1000 + t3.i*100 + t2.i*10 + t1.i DAY) AS date
            FROM 
                (SELECT 0 i UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) t1
                CROSS JOIN (SELECT 0 i UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) t2
                CROSS JOIN (SELECT 0 i UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) t3
                CROSS JOIN (SELECT 0 i UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) t4
        ) date_series
        left join (" . $log . ") l on l.date=date_series.date
        left join (" . $user . ") u on u.created=date_series.date
        WHERE date_series.date BETWEEN :startDate AND :endDate
        GROUP BY date_series.date
        ORDER BY date_series.date";
        
        // 原有的Yii2调用现在可以直接工作！
        $list = \Yii::$app->db->createCommand($sql . $p_str)
            ->bindValue(':startDate', $params['start_date'])
            ->bindValue(':endDate', $params['end_date'])
            ->queryAll();
        
        $count = (strtotime($params['end_date']) - strtotime($params['start_date'])) / 86400 + 1;

        return ['list' => $list, 'count' => $count];
    }

    /**
     * 使用新的助手函数的版本 - 推荐方式
     * 
     * 虽然兼容性版本可以工作，但我们也可以使用更现代的助手函数
     * 
     * @param array $params
     * @return array
     */
    public static function userWithHelper($params = [])
    {
        // 使用新的助手函数，更简洁
        $sql = "SELECT COUNT(*) as total FROM users WHERE is_dealer = 1";
        
        // 方式1: 使用db_query助手函数
        $result1 = db_query($sql);
        
        // 方式2: 使用db_query_one助手函数
        $result2 = db_query_one($sql);
        
        // 方式3: 使用完整的DatabaseHelper
        $result3 = DatabaseHelper::createCommand($sql)->queryAll();
        
        // 方式4: 使用yii_db()助手函数（完全兼容Yii2语法）
        $result4 = yii_db()->createCommand($sql)->queryAll();
        
        return $result4;
    }

    /**
     * 事务处理示例
     * 
     * @param array $data
     * @return bool
     */
    public static function transactionExample($data)
    {
        try {
            return DatabaseHelper::transaction(function() use ($data) {
                // 在事务中执行多个操作
                $sql1 = "INSERT INTO users (username, email) VALUES (?, ?)";
                db_execute($sql1, [$data['username'], $data['email']]);
                
                $userId = DatabaseHelper::createCommand()->getLastInsertID();
                
                $sql2 = "INSERT INTO user_profiles (user_id, nickname) VALUES (?, ?)";
                db_execute($sql2, [$userId, $data['nickname']]);
                
                return true;
            });
        } catch (\Exception $e) {
            return false;
        }
    }
}

/**
 * 用法说明和迁移指南
 * 
 * 1. 原有代码完全兼容
 * ==================
 * 您的原有DayDataService.php文件可以直接复制过来使用，无需任何修改！
 * 所有的Yii2 ActiveRecord调用、\Yii::$app->db调用都可以正常工作。
 * 
 * 2. 三种使用方式
 * ===============
 * 
 * A) 完全兼容模式（推荐用于快速迁移）：
 *    \Yii::$app->db->createCommand($sql)->queryAll();
 * 
 * B) 助手函数模式（推荐用于新代码）：
 *    db_query($sql, $params);
 *    db_query_one($sql, $params);
 *    db_execute($sql, $params);
 * 
 * C) 直接使用DatabaseHelper：
 *    DatabaseHelper::createCommand($sql)->queryAll();
 * 
 * 3. 模型迁移
 * ==========
 * 原有的模型类需要继承自：
 * Webman\Yii2Orm\ActiveRecord\ActiveRecord
 * 
 * 4. 配置数据库
 * ============
 * 在config/database.php中配置您的数据库连接即可。
 * 
 * 5. 性能说明
 * ==========
 * 底层使用Illuminate Database ORM，性能优异，支持连接池和查询缓存。
 */