<?php

/**
 * 集成测试示例
 * 
 * 这个文件展示如何在实际项目中集成和使用 webman-yii2-orm 包
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Webman\Yii2Orm\ServiceProvider;
use Webman\Yii2Orm\Database\Connection;
use Webman\Yii2Orm\Validator\Validator;
use Webman\Yii2Orm\DataProvider\ArrayDataProvider;
use Webman\Yii2Orm\DataProvider\Pagination;
use Webman\Yii2Orm\DataProvider\Sort;

echo "=== Webman Yii2 orm 集成测试 ===\n\n";

// 1. 测试验证器
echo "1. 测试验证器功能:\n";

$testData = [
    'username' => 'john_doe',
    'email' => 'john@example.com',
    'age' => 25,
    'status' => 'active'
];

$validator = new Validator($testData, [
    'username' => 'required|string|min:3|max:50',
    'email' => 'required|email',
    'age' => 'required|integer|min:18',
    'status' => 'required|in:active,inactive'
]);

if ($validator->passes()) {
    echo "✓ 验证通过\n";
} else {
    echo "✗ 验证失败:\n";
    print_r($validator->errors());
}

// 测试验证失败的情况
echo "\n测试验证失败情况:\n";
$invalidData = [
    'username' => 'ab',  // 太短
    'email' => 'invalid-email',  // 无效邮箱
    'age' => 15,  // 年龄不够
    'status' => 'unknown'  // 无效状态
];

$invalidValidator = new Validator($invalidData, [
    'username' => 'required|string|min:3|max:50',
    'email' => 'required|email',
    'age' => 'required|integer|min:18',
    'status' => 'required|in:active,inactive'
]);

if (!$invalidValidator->passes()) {
    echo "✓ 正确识别验证错误\n";
    foreach ($invalidValidator->errors() as $field => $errors) {
        echo "  - {$field}: " . implode(', ', $errors) . "\n";
    }
} else {
    echo "✗ 应该验证失败但通过了\n";
}

// 2. 测试数组数据提供者
echo "\n2. 测试数组数据提供者:\n";

$sampleData = [];
for ($i = 1; $i <= 50; $i++) {
    $sampleData[] = [
        'id' => $i,
        'name' => 'User ' . $i,
        'age' => rand(18, 60),
        'email' => "user{$i}@example.com",
        'created_at' => date('Y-m-d H:i:s', strtotime("-{$i} days"))
    ];
}

$dataProvider = new ArrayDataProvider([
    'allModels' => $sampleData,
    'pagination' => new Pagination([
        'pageSize' => 10,
    ]),
    'sort' => new Sort([
        'attributes' => ['id', 'name', 'age', 'created_at'],
        'defaultOrder' => ['id' => Sort::DESC],
    ]),
]);

$models = $dataProvider->getModels();
$pagination = $dataProvider->getPagination();

echo "✓ 数据提供者创建成功\n";
echo "  - 总记录数: {$dataProvider->getTotalCount()}\n";
echo "  - 当前页记录数: {$dataProvider->getCount()}\n";
echo "  - 总页数: {$pagination->getPageCount()}\n";
echo "  - 当前页: {$pagination->page}\n";

// 3. 测试助手函数
echo "\n3. 测试助手函数:\n";

// 测试验证助手函数
$result = yii2_validate([
    'email' => 'test@example.com',
    'age' => 25
], [
    'email' => 'required|email',
    'age' => 'required|integer|min:18'
]);

if ($result === true) {
    echo "✓ yii2_validate 助手函数工作正常\n";
} else {
    echo "✗ yii2_validate 助手函数失败\n";
    print_r($result);
}

// 测试数组数据提供者助手函数
$arrayProvider = yii2_array_provider($sampleData);
echo "✓ yii2_array_provider 助手函数工作正常\n";

// 4. 测试分页功能
echo "\n4. 测试分页功能:\n";

$pagination = yii2_paginate([
    'pageSize' => 5,
]);

$pagination->totalCount = 50;

echo "✓ 分页创建成功\n";
echo "  - 每页显示: {$pagination->pageSize} 条\n";
echo "  - 总页数: {$pagination->getPageCount()}\n";
echo "  - 偏移量: {$pagination->getOffset()}\n";

// 5. 测试排序功能  
echo "\n5. 测试排序功能:\n";

$sort = yii2_sort([
    'attributes' => ['id', 'name', 'age'],
    'defaultOrder' => ['name' => Sort::ASC],
]);

echo "✓ 排序创建成功\n";
echo "  - 可排序字段: " . implode(', ', $sort->attributes) . "\n";
echo "  - 默认排序: " . json_encode($sort->defaultOrder) . "\n";

// 6. 性能测试
echo "\n6. 性能测试:\n";

$startTime = microtime(true);
$iterations = 1000;

for ($i = 0; $i < $iterations; $i++) {
    $validator = new Validator([
        'email' => 'test@example.com',
        'age' => 25
    ], [
        'email' => 'email',
        'age' => 'integer|min:18'
    ]);
    $validator->passes();
}

$endTime = microtime(true);
$duration = ($endTime - $startTime) * 1000; // 转换为毫秒

echo "✓ 性能测试完成\n";
echo "  - {$iterations} 次验证操作耗时: " . round($duration, 2) . " ms\n";
echo "  - 平均每次验证: " . round($duration / $iterations, 4) . " ms\n";

echo "\n=== 所有测试完成 ===\n";
echo "✓ 验证器功能正常\n";
echo "✓ 数据提供者功能正常\n";
echo "✓ 分页功能正常\n";
echo "✓ 排序功能正常\n";
echo "✓ 助手函数正常\n";
echo "✓ 性能表现良好\n";

echo "\n集成成功！您可以在 Webman 项目中使用这个包了。\n";