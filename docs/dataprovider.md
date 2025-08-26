# DataProvider 使用指南

## 简介

DataProvider 提供了统一的数据接口，支持分页、排序、过滤等功能。它是构建列表页面和 API 接口的理想选择。

## ActiveDataProvider

ActiveDataProvider 用于处理 ActiveRecord 查询，支持数据库查询的分页和排序。

### 基本使用

```php
use App\Model\User;
use Webman\Yii2Bridge\DataProvider\ActiveDataProvider;

// 创建基础数据提供者
$dataProvider = new ActiveDataProvider([
    'query' => User::find(),
]);

// 获取数据
$users = $dataProvider->getModels();
$pagination = $dataProvider->getPagination();
```

### 配置分页

```php
use Webman\Yii2Bridge\DataProvider\Pagination;

$dataProvider = new ActiveDataProvider([
    'query' => User::find(),
    'pagination' => new Pagination([
        'pageSize' => 20,  // 每页显示数量
    ]),
]);
```

### 配置排序

```php
use Webman\Yii2Bridge\DataProvider\Sort;

$dataProvider = new ActiveDataProvider([
    'query' => User::find(),
    'sort' => new Sort([
        'attributes' => ['id', 'username', 'email', 'created_at'],
        'defaultOrder' => ['created_at' => Sort::DESC],
    ]),
]);
```

### 完整配置示例

```php
$dataProvider = new ActiveDataProvider([
    'query' => User::find()->where('status', 1),
    'pagination' => new Pagination([
        'pageSize' => 15,
        'pageParam' => 'page',
        'pageSizeParam' => 'per_page',
    ]),
    'sort' => new Sort([
        'attributes' => [
            'id',
            'username', 
            'email',
            'created_at',
            'status'
        ],
        'defaultOrder' => ['id' => Sort::DESC],
        'sortParam' => 'sort',
    ]),
]);
```

## ArrayDataProvider

ArrayDataProvider 用于处理数组数据，当您需要对已有的数组数据进行分页和排序时使用。

### 基本使用

```php
use Webman\Yii2Bridge\DataProvider\ArrayDataProvider;

$data = [
    ['id' => 1, 'name' => 'Alice', 'age' => 25],
    ['id' => 2, 'name' => 'Bob', 'age' => 30],
    ['id' => 3, 'name' => 'Charlie', 'age' => 35],
    // ... 更多数据
];

$dataProvider = new ArrayDataProvider([
    'allModels' => $data,
]);
```

### 配置排序和分页

```php
$dataProvider = new ArrayDataProvider([
    'allModels' => $data,
    'pagination' => new Pagination([
        'pageSize' => 10,
    ]),
    'sort' => new Sort([
        'attributes' => ['id', 'name', 'age'],
        'defaultOrder' => ['age' => Sort::DESC],
    ]),
]);
```

### 自定义键值

```php
$dataProvider = new ArrayDataProvider([
    'allModels' => $data,
    'key' => 'id',  // 使用 id 作为键
    // 或者使用回调函数
    'key' => function($model, $index) {
        return $model['id'];
    },
]);
```

## 分页功能

### Pagination 类

```php
use Webman\Yii2Bridge\DataProvider\Pagination;

$pagination = new Pagination([
    'page' => 1,           // 当前页码
    'pageSize' => 20,      // 每页数量
    'totalCount' => 0,     // 总记录数（自动计算）
    'pageParam' => 'page', // 页码参数名
    'pageSizeParam' => 'per_page', // 每页数量参数名
]);
```

### 分页方法

```php
// 获取分页信息
$pageCount = $pagination->getPageCount();    // 总页数
$offset = $pagination->getOffset();          // 偏移量
$limit = $pagination->getLimit();            // 限制数量

// 检查分页状态
$hasPrev = $pagination->hasPrevPage();       // 是否有上一页
$hasNext = $pagination->hasNextPage();       // 是否有下一页

// 获取页码
$prevPage = $pagination->getPrevPage();      // 上一页页码
$nextPage = $pagination->getNextPage();      // 下一页页码
```

### 生成分页链接

```php
// 创建分页链接
$firstPageUrl = $pagination->createUrl(1);
$lastPageUrl = $pagination->createUrl($pagination->getPageCount());

// 获取所有链接
$links = $pagination->getLinks();
/*
返回格式：
[
    'first' => '/users?page=1',
    'prev' => '/users?page=2',
    'next' => '/users?page=4', 
    'last' => '/users?page=10'
]
*/
```

### 分页数组格式

```php
$paginationArray = $pagination->toArray();
/*
返回格式：
[
    'current_page' => 3,
    'per_page' => 20,
    'total' => 200,
    'last_page' => 10,
    'has_prev' => true,
    'has_next' => true,
    'links' => [...]
]
*/
```

## 排序功能

### Sort 类

```php
use Webman\Yii2Bridge\DataProvider\Sort;

$sort = new Sort([
    'attributes' => [
        'id',
        'username',
        'email',
        'created_at'
    ],
    'defaultOrder' => [
        'created_at' => Sort::DESC
    ],
    'sortParam' => 'sort',
]);
```

### 排序方法

```php
// 获取排序条件
$orders = $sort->getOrders();
/*
返回格式：
[
    'created_at' => 'desc',
    'username' => 'asc'
]
*/

// 获取指定属性的排序方向
$direction = $sort->getAttributeOrder('username'); // 'asc' 或 'desc'

// 检查属性是否可排序
$canSort = $sort->hasAttribute('username');
```

### 生成排序链接

```php
// 创建排序链接
$ascUrl = $sort->createUrl('username', Sort::ASC);
$descUrl = $sort->createUrl('username', Sort::DESC);

// 获取所有排序链接
$links = $sort->getLinks();
/*
返回格式：
[
    'username' => [
        'asc' => '/users?sort=username',
        'desc' => '/users?sort=-username',
        'current' => 'asc'
    ],
    // ... 其他字段
]
*/
```

### 排序参数格式

URL 中的排序参数格式：
- `?sort=username` - 按 username 升序
- `?sort=-username` - 按 username 降序  
- `?sort=username,-created_at` - 多字段排序

## 在控制器中使用

### 用户列表示例

```php
class UserController
{
    public function index(Request $request)
    {
        // 构建查询
        $query = User::find();
        
        // 添加搜索条件
        if ($username = $request->get('username')) {
            $query->where('username', 'like', "%{$username}%");
        }
        
        if ($email = $request->get('email')) {
            $query->where('email', 'like', "%{$email}%");
        }
        
        // 创建数据提供者
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => new Pagination([
                'pageSize' => 20,
            ]),
            'sort' => new Sort([
                'attributes' => ['id', 'username', 'email', 'created_at'],
                'defaultOrder' => ['created_at' => Sort::DESC],
            ]),
        ]);
        
        return ResponseFormatter::paginated($dataProvider);
    }
}
```

### 返回数据格式

```php
use Webman\Yii2Bridge\Tools\ResponseFormatter;

// 使用内置的分页响应格式
return ResponseFormatter::paginated($dataProvider, '获取成功');

/*
返回 JSON 格式：
{
    "code": 0,
    "message": "获取成功",
    "data": {
        "items": [...],
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total": 100,
            "last_page": 5,
            "has_prev": false,
            "has_next": true,
            "links": {...}
        },
        "sort": {
            "username": {
                "asc": "/users?sort=username",
                "desc": "/users?sort=-username",
                "current": null
            }
        }
    }
}
*/
```

## 高级用法

### 自定义数据提供者

```php
class CustomDataProvider extends BaseDataProvider
{
    public $apiUrl;
    
    protected function prepareModels()
    {
        // 从 API 获取数据
        $response = file_get_contents($this->apiUrl . '?' . http_build_query([
            'page' => $this->pagination->page,
            'per_page' => $this->pagination->pageSize,
        ]));
        
        $data = json_decode($response, true);
        return $data['items'] ?? [];
    }
    
    protected function prepareTotalCount()
    {
        // 获取总数
        $response = file_get_contents($this->apiUrl . '/count');
        $data = json_decode($response, true);
        return $data['total'] ?? 0;
    }
    
    protected function prepareKeys($models)
    {
        return array_column($models, 'id');
    }
}
```

### 缓存支持

```php
class CachedActiveDataProvider extends ActiveDataProvider
{
    public $cacheKey;
    public $cacheDuration = 300; // 5分钟
    
    public function getModels()
    {
        if ($this->cacheKey) {
            $cacheKey = $this->cacheKey . '_' . md5(serialize([
                $this->query->toSql(),
                $this->pagination->page,
                $this->sort->getOrders(),
            ]));
            
            $cached = cache()->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        $models = parent::getModels();
        
        if ($this->cacheKey) {
            cache()->set($cacheKey, $models, $this->cacheDuration);
        }
        
        return $models;
    }
}
```

## 最佳实践

### 1. 合理设置页面大小

```php
$pagination = new Pagination([
    'pageSize' => min($request->get('per_page', 20), 100), // 限制最大100条
]);
```

### 2. 安全的排序字段

```php
$sort = new Sort([
    'attributes' => [
        'id',
        'username',
        'created_at',
        // 只允许这些字段排序
    ],
]);
```

### 3. 性能优化

```php
// 对于大数据量，考虑使用游标分页
$query = User::find()
    ->where('id', '>', $lastId)  // 游标分页
    ->limit(20);
```

### 4. 统一的响应格式

```php
class ApiController
{
    protected function respondWithPagination($dataProvider, $message = 'Success')
    {
        return ResponseFormatter::paginated($dataProvider, $message);
    }
}
```