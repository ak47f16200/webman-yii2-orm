<?php

namespace Webman\Yii2Orm\ActiveRecord;

use Webman\Yii2Orm\Database\Connection;
use Webman\Yii2Orm\Database\Query;
use Webman\Yii2Orm\Validator\Validator;
use Webman\Yii2Orm\Events\EventTrait;
use Webman\Yii2Orm\Events\Event;
use Webman\Yii2Orm\Behaviors\Behavior;

abstract class ActiveRecord
{
    use EventTrait;
    
    // 事件常量
    const EVENT_BEFORE_INSERT = 'beforeInsert';
    const EVENT_AFTER_INSERT = 'afterInsert';
    const EVENT_BEFORE_UPDATE = 'beforeUpdate';
    const EVENT_AFTER_UPDATE = 'afterUpdate';
    const EVENT_BEFORE_DELETE = 'beforeDelete';
    const EVENT_AFTER_DELETE = 'afterDelete';
    const EVENT_BEFORE_SAVE = 'beforeSave';
    const EVENT_AFTER_SAVE = 'afterSave';
    const EVENT_BEFORE_VALIDATE = 'beforeValidate';
    const EVENT_AFTER_VALIDATE = 'afterValidate';
    
    protected $attributes = [];
    protected $oldAttributes = [];
    protected $isNewRecord = true;
    protected $errors = [];
    
    // 行为管理
    private $_behaviors = [];
    
    // 需要子类重写的属性
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $guarded = [];
    
    // 数据库连接配置
    protected static $connectionName = null; // 指定连接名称
    
    public function __construct($attributes = [])
    {
        $this->attachBehaviors($this->behaviors());
        
        if (!empty($attributes)) {
            $this->setAttributes($attributes);
        }
    }
    
    /**
     * 获取表名
     */
    public function tableName()
    {
        if ($this->table) {
            return $this->table;
        }
        
        // 根据类名自动生成表名
        $className = (new \ReflectionClass($this))->getShortName();
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className)) . 's';
    }
    
    /**
     * 获取主键名
     */
    public function primaryKeyName()
    {
        return $this->primaryKey;
    }
    
    /**
     * 行为配置
     */
    public function behaviors()
    {
        return [];
    }
    
    /**
     * 附加行为
     */
    public function attachBehaviors($behaviors)
    {
        foreach ($behaviors as $name => $behavior) {
            $this->attachBehavior($name, $behavior);
        }
    }
    
    /**
     * 附加单个行为
     */
    public function attachBehavior($name, $behavior)
    {
        $this->detachBehavior($name);
        
        if ($behavior instanceof Behavior) {
            $behavior->attach($this);
            $this->_behaviors[$name] = $behavior;
        } elseif (is_array($behavior)) {
            $class = $behavior['class'] ?? null;
            if ($class) {
                unset($behavior['class']);
                $behaviorInstance = new $class($behavior);
                $behaviorInstance->attach($this);
                $this->_behaviors[$name] = $behaviorInstance;
            }
        }
    }
    
    /**
     * 分离行为
     */
    public function detachBehavior($name)
    {
        if (isset($this->_behaviors[$name])) {
            $this->_behaviors[$name]->detach();
            unset($this->_behaviors[$name]);
        }
    }
    
    /**
     * 获取行为
     */
    public function getBehavior($name)
    {
        return $this->_behaviors[$name] ?? null;
    }
    
    /**
     * 获取所有行为
     */
    public function getBehaviors()
    {
        return $this->_behaviors;
    }
    
    /**
     * 检查属性是否存在
     */
    public function hasAttribute($name)
    {
        return array_key_exists($name, $this->attributes);
    }
    
    /**
     * 验证规则
     */
    public function rules()
    {
        return [];
    }
    
    /**
     * 属性标签
     */
    public function attributeLabels()
    {
        return [];
    }
    
    /**
     * 设置属性
     */
    public function setAttributes($attributes, $safeOnly = true)
    {
        if (empty($attributes)) {
            return;
        }
        
        foreach ($attributes as $name => $value) {
            if ($safeOnly && !$this->isSafeAttribute($name)) {
                continue;
            }
            $this->setAttribute($name, $value);
        }
    }
    
    /**
     * 设置单个属性
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
    }
    
    /**
     * 获取属性值
     */
    public function getAttribute($name)
    {
        return $this->attributes[$name] ?? null;
    }
    
    /**
     * 获取所有属性
     */
    public function getAttributes()
    {
        return $this->attributes;
    }
    
    /**
     * 检查属性是否安全
     */
    protected function isSafeAttribute($attribute)
    {
        if (!empty($this->fillable)) {
            return in_array($attribute, $this->fillable);
        }
        
        if (!empty($this->guarded)) {
            return !in_array($attribute, $this->guarded);
        }
        
        return true;
    }
    
    /**
     * 魔术方法 - 获取属性
     */
    public function __get($name)
    {
        return $this->getAttribute($name);
    }
    
    /**
     * 魔术方法 - 设置属性
     */
    public function __set($name, $value)
    {
        $this->setAttribute($name, $value);
    }
    
    /**
     * 魔术方法 - 检查属性是否存在
     */
    public function __isset($name)
    {
        return isset($this->attributes[$name]);
    }
    
    /**
     * 验证数据
     */
    public function validate()
    {
        $this->errors = [];
        
        // 触发验证前事件
        if (!$this->trigger(self::EVENT_BEFORE_VALIDATE)) {
            return false;
        }
        
        $rules = $this->rules();
        
        if (empty($rules)) {
            $valid = true;
        } else {
            $validator = new Validator($this->attributes, $rules);
            $valid = $validator->passes();
            if (!$valid) {
                $this->errors = $validator->errors();
            }
        }
        
        // 触发验证后事件
        $this->trigger(self::EVENT_AFTER_VALIDATE);
        
        return $valid;
    }
    
    /**
     * 获取验证错误
     */
    public function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * 检查是否有错误
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }
    
    /**
     * 获取第一个错误信息
     */
    public function getFirstError($attribute = null)
    {
        if ($attribute !== null) {
            return $this->errors[$attribute][0] ?? null;
        }
        
        foreach ($this->errors as $errors) {
            return $errors[0] ?? null;
        }
        
        return null;
    }
    
    /**
     * 保存记录
     */
    public function save($validate = true)
    {
        if ($validate && !$this->validate()) {
            return false;
        }
        
        // 触发保存前事件
        if (!$this->trigger(self::EVENT_BEFORE_SAVE)) {
            return false;
        }
        
        $result = false;
        
        if ($this->isNewRecord) {
            $result = $this->insert();
        } else {
            $result = $this->update();
        }
        
        if ($result) {
            // 触发保存后事件
            $this->trigger(self::EVENT_AFTER_SAVE);
        }
        
        return $result;
    }
    
    /**
     * 插入新记录
     */
    protected function insert()
    {
        // 触发插入前事件
        if (!$this->trigger(self::EVENT_BEFORE_INSERT)) {
            return false;
        }
        
        $attributes = $this->attributes;
        
        // 移除主键（如果是自增的话）
        if (isset($attributes[$this->primaryKey]) && empty($attributes[$this->primaryKey])) {
            unset($attributes[$this->primaryKey]);
        }
        
        $id = Connection::table($this->tableName())->insertGetId($attributes);
        
        if ($id) {
            $this->setAttribute($this->primaryKey, $id);
            $this->isNewRecord = false;
            $this->oldAttributes = $this->attributes;
            
            // 触发插入后事件
            $this->trigger(self::EVENT_AFTER_INSERT);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * 更新记录
     */
    protected function update()
    {
        // 触发更新前事件
        if (!$this->trigger(self::EVENT_BEFORE_UPDATE)) {
            return false;
        }
        
        $pk = $this->getAttribute($this->primaryKey);
        if (empty($pk)) {
            return false;
        }
        
        // 只更新已更改的属性
        $changedAttributes = [];
        foreach ($this->attributes as $name => $value) {
            if (!isset($this->oldAttributes[$name]) || $this->oldAttributes[$name] !== $value) {
                $changedAttributes[$name] = $value;
            }
        }
        
        if (empty($changedAttributes)) {
            return true; // 没有更改
        }
        
        $result = Connection::table($this->tableName())
            ->where($this->primaryKey, $pk)
            ->update($changedAttributes);
        
        if ($result !== false) {
            $this->oldAttributes = $this->attributes;
            
            // 触发更新后事件
            $this->trigger(self::EVENT_AFTER_UPDATE);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * 删除记录
     */
    public function delete()
    {
        // 触发删除前事件
        if (!$this->trigger(self::EVENT_BEFORE_DELETE)) {
            return false;
        }
        
        $pk = $this->getAttribute($this->primaryKey);
        if (empty($pk)) {
            return false;
        }
        
        $result = Connection::table($this->tableName())
            ->where($this->primaryKey, $pk)
            ->delete() > 0;
            
        if ($result) {
            // 触发删除后事件
            $this->trigger(self::EVENT_AFTER_DELETE);
        }
        
        return $result;
    }
    
    /**
     * 刷新记录
     */
    public function refresh()
    {
        $pk = $this->getAttribute($this->primaryKey);
        if (empty($pk)) {
            return false;
        }
        
        $record = static::findOne($pk);
        if ($record) {
            $this->attributes = $record->attributes;
            $this->oldAttributes = $this->attributes;
            return true;
        }
        
        return false;
    }
    
    /**
     * 检查是否为新记录
     */
    public function isNewRecord()
    {
        return $this->isNewRecord;
    }
    
    /**
     * 创建查询对象
     */
    public static function find()
    {
        return new ActiveQuery(static::class);
    }
    
    /**
     * 获取数据库连接 - 兼容Yii2的getDb()方法
     * 支持 Model::getDb()->beginTransaction() 等写法
     * 不同模型可以连接不同的数据库
     * 
     * @return \Webman\Yii2Orm\Database\DatabaseConnection
     */
    public static function getDb()
    {
        // 获取当前模型的连接名称
        $connectionName = static::getConnectionName();
        return new \Webman\Yii2Orm\Database\DatabaseConnection($connectionName);
    }
    
    /**
     * 获取连接名称
     * 子类可以重写此方法指定不同的数据库连接
     * 
     * @return string|null
     */
    public static function getConnectionName()
    {
        return static::$connectionName;
    }
    
    /**
     * 设置连接名称
     * 
     * @param string|null $name
     */
    public static function setConnectionName($name)
    {
        static::$connectionName = $name;
    }
    
    /**
     * 查找单个记录
     */
    public static function findOne($condition)
    {
        $query = static::find();
        
        if (is_scalar($condition)) {
            $query->where((new static)->primaryKey, $condition);
        } elseif (is_array($condition)) {
            foreach ($condition as $key => $value) {
                $query->where($key, $value);
            }
        }
        
        return $query->one();
    }
    
    /**
     * 查找所有记录
     */
    public static function findAll($condition = [])
    {
        $query = static::find();
        
        if (is_array($condition) && !empty($condition)) {
            foreach ($condition as $key => $value) {
                $query->where($key, $value);
            }
        }
        
        return $query->all();
    }
    
    /**
     * 从数组创建模型实例
     */
    public static function fromArray(array $data)
    {
        $model = new static();
        $model->attributes = $data;
        $model->oldAttributes = $data;
        $model->isNewRecord = false;
        
        return $model;
    }
    
    /**
     * 转换为数组
     */
    public function toArray()
    {
        return $this->attributes;
    }
    
    /**
     * 转换为 JSON
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }
    
    /**
     * Has One 关联
     */
    protected function hasOne($relatedModel, $foreignKey, $localKey = null)
    {
        $localKey = $localKey ?: $this->primaryKey;
        $localValue = $this->getAttribute($localKey);
        
        if (!$localValue) {
            return null;
        }
        
        return $relatedModel::findOne([$foreignKey => $localValue]);
    }
    
    /**
     * Has Many 关联
     */
    protected function hasMany($relatedModel, $foreignKey, $localKey = null)
    {
        $localKey = $localKey ?: $this->primaryKey;
        $localValue = $this->getAttribute($localKey);
        
        if (!$localValue) {
            return [];
        }
        
        return $relatedModel::findAll([$foreignKey => $localValue]);
    }
    
    /**
     * Belongs To 关联
     */
    protected function belongsTo($relatedModel, $foreignKey, $ownerKey = null)
    {
        $ownerKey = $ownerKey ?: (new $relatedModel)->primaryKey;
        $foreignValue = $this->getAttribute($foreignKey);
        
        if (!$foreignValue) {
            return null;
        }
        
        return $relatedModel::findOne([$ownerKey => $foreignValue]);
    }
}