# Elasticsearch 客户端 - Hyperf 版本 (支持 Hyperf 3.0+)

## 简介

这是一个基于 Hyperf 框架的 Elasticsearch 客户端扩展包，支持Hyperf3.0，提供了简单易用的 ORM 风格 API，让你在 PHP 项目中像使用数据库Model一样优雅地使用 Elasticsearch。

## 安装

通过 Composer 安装:

```bash
composer require jhansin/elasticsearch
```

## 配置

在 Hyperf 项目中，你需要配置 Elasticsearch 连接信息。创建或编辑 `config/autoload/elasticsearch.php` 文件：

```php
<?php
return [
    'default' => [
        'hosts' => [env('ES_DEFAULT_HOST', 'http://127.0.0.1:9200')],
        'max_connections' => env('ES_MAX_CONNECTIONS', 10),
        'timeout' => env('ES_TIMEOUT', 2.0),
        'username' => env('ES_USERNAME', 'xxx'),
        'password' => env('ES_PASSWORD', 'xxx'),
    ],
    'order' => [
        'hosts' => [env('ES_ORDER_HOST', 'http://127.0.0.1:9200')],
        'max_connections' => env('ES_MAX_CONNECTIONS', 10),
        'timeout' => env('ES_TIMEOUT', 2.0),
        'username' => env('ES_USERNAME', 'xxx'),
        'password' => env('ES_PASSWORD', 'xxx'),
    ],
];
```

然后在 `.env` 文件中配置:

```bash
ES_DEFAULT_HOST=http://127.0.0.1:9200
ES_USERNAME=
ES_PASSWORD=
ES_MAX_CONNECTIONS=50
ES_TIMEOUT=0
```

## 模型定义

Elasticsearch 中的索引相当于 MySQL 中的表。你可以创建模型类来映射 Elasticsearch 中的索引。

```php
<?php

declare(strict_types=1);

namespace App\EsModel;

use Jhansin\Elasticsearch\BaseEsModel;

class OrderModel extends BaseEsModel
{
class OrderEs extends BaseEsModel
{
    /**
     * 可选：指定使用的连接名称，对应配置文件中的键名
     */
    protected $connection = 'default';

    /**
     * 索引.
     * */
    protected $index = 'gorder_index';
}
    
    /**
     * 字段类型定义，用于创建索引
     */
    protected $casts = [
        'name' => [
            'type' => 'text',
            'analyzer' => 'ik_max_word',      // 使用 IK 分词器最大分词
            'search_analyzer' => 'ik_smart'   // 搜索时使用 IK 智能分词
        ],
    ];
    

}
```

## 索引管理

### 创建索引

```php
<?php
// 根据模型中定义的字段类型创建索引
OrderModel::createIndex();

// 创建索引并设置分片数和副本数
OrderModel::createIndex([
    'number_of_shards' => 3,
    'number_of_replicas' => 1
]);

// 创建索引并自定义映射
OrderModel::createIndex([
    'number_of_shards' => 3,
    'number_of_replicas' => 1
], [
    'properties' => [
        'name' => [
            'type' => 'text',
            'analyzer' => 'ik_max_word',
            'search_analyzer' => 'ik_smart'
        ]
    ]
]);
```

### 删除索引

```php
<?php
// 删除索引
OrderModel::deleteIndex();
```

### 索引是否存在

```php
<?php
// 检查索引是否存在
$exists = OrderModel::existsIndex();
```

### 更新映射

```php
<?php
// 更新映射
OrderModel::updateMapping([
    'properties' => [
        'new_field' => [
            'type' => 'keyword'
        ]
    ]
]);
```

### 更新设置

```php
<?php
// 更新设置
OrderModel::updateSetting([
    'number_of_replicas' => 2
]);
```

## 数据操作

### 新增数据

```php
<?php
// 单条创建
OrderModel::query()->create([
    'id' => '1',
    'name' => '张三',
    'price' => 99.9,
    'created_at' => date('Y-m-d H:i:s')
]);

// 批量创建
OrderModel::query()->insert([
    [
        'id' => '2',
        'name' => '李四',
        'price' => 88.8,
        'created_at' => date('Y-m-d H:i:s')
    ],
    [
        'id' => '3',
        'name' => '王五',
        'price' => 77.7,
        'created_at' => date('Y-m-d H:i:s')
    ]
]);
```

### 更新数据

```php
<?php
// 根据ID更新
OrderModel::query()->update([
    'name' => '张三 (已更新)',
    'price' => 199.9
], '1');
```

### 删除数据

```php
<?php
// 根据ID删除
OrderModel::query()->delete('1');

// 根据条件批量删除
OrderModel::query()->where('price', '>=', 100)->delete();
```

## 查询数据

### 基本查询

```php
<?php
// 查询全部数据
$all = OrderModel::query()->get()->toArray();

// 查询指定字段
$orders = OrderModel::query()
    ->select(['id', 'name', 'price'])
    ->get()
    ->toArray();

// 根据 ID 查询
$order = OrderModel::query()->find('1');
$orders = OrderModel::query()->findMany(['1', '2', '3']);

// 查询单条数据
$first = OrderModel::query()->first();
```

### 条件查询

```php
<?php
// 等值查询
$orders = OrderModel::query()
    ->where('name', '张三')
    ->get()
    ->toArray();

// 比较查询
$orders = OrderModel::query()
    ->where('price', '>', 50)
    ->get()
    ->toArray();

// IN 查询
$orders = OrderModel::query()
    ->whereIn('name', ['张三', '李四'])
    ->get()
    ->toArray();

// NOT IN 查询
$orders = OrderModel::query()
    ->whereNotIn('name', ['张三', '李四'])
    ->get()
    ->toArray();

// 模糊查询
$orders = OrderModel::query()
    ->whereLike('name', '张')
    ->get()
    ->toArray();

// 前缀查询
$orders = OrderModel::query()
    ->where('name', 'prefix', '张')
    ->get()
    ->toArray();

// 正则表达式查询
$orders = OrderModel::query()
    ->where('name', 'regex', '张.*')
    ->get()
    ->toArray();

// 范围查询
$orders = OrderModel::query()
    ->whereBetween('price', [50, 100])
    ->get()
    ->toArray();

// 组合查询（AND）
$orders = OrderModel::query()
    ->where('price', '>=', 50)
    ->where('price', '<=', 100)
    ->get()
    ->toArray();

// 组合查询（OR）- should条件
$orders = OrderModel::query()
    ->should('price', '=', 50)
    ->should('name', '=', '张三')
    ->get()
    ->toArray();
```

### 全文搜索

```php
<?php
// Match查询
$orders = OrderModel::query()
    ->match('name', '张三')
    ->get()
    ->toArray();

// 短语匹配查询
$orders = OrderModel::query()
    ->matchPhrase('name', '张三')
    ->get()
    ->toArray();
```

### 排序和分页

```php
<?php
// 排序
$orders = OrderModel::query()
    ->orderBy('price', 'desc') // 或者使用 true/false 表示降序/升序
    ->get()
    ->toArray();

// 分页方式一：偏移+限制
$page = 1;
$size = 10;
$orders = OrderModel::query()
    ->skip(($page - 1) * $size)
    ->take($size)
    ->get()
    ->toArray();

// 分页方式二：使用分页器
$page = 1;
$size = 10;
$paginator = OrderModel::query()->page($size, $page);
$orders = $paginator->toArray();
// 包含分页信息：total, page, size, data
```

### 高亮显示

```php
<?php
// 高亮搜索结果
$orders = OrderModel::query()
    ->match('name', '张三')
    ->highlight([
        'fields' => [
            'name' => new \stdClass(),
        ]
    ])
    ->get()
    ->toArray();
```

### 聚合查询

```php
<?php
// 求和
$sum = OrderModel::query()
    ->sum('price');

// 平均值
$avg = OrderModel::query()
    ->avg('price');

// 最大值
$max = OrderModel::query()
    ->max('price');

// 最小值
$min = OrderModel::query()
    ->min('price');

// 统计数量
$count = OrderModel::query()->count();

// 自定义聚合
$result = OrderModel::query()->groupBy([
    'price_stats' => [
        'stats' => [
            'field' => 'price'
        ]
    ],
    'price_range' => [
        'range' => [
            'field' => 'price',
            'ranges' => [
                ['to' => 50],
                ['from' => 50, 'to' => 100],
                ['from' => 100]
            ]
        ]
    ]
]);
```

## 高级用法

### 复杂查询 (使用原生DSL语句)

```php
<?php
$result = OrderModel::query()->raw([
    'query' => [
        'bool' => [
            'must' => [
                ['match' => ['name' => '张三']]
            ],
            'filter' => [
                ['range' => ['price' => ['gte' => 50]]]
            ]
        ]
    ]
]);
```

### 批量操作 (Bulk API)

```php
<?php
$operations = [];
$operations[] = ['create' => ['_id' => '4', '_index' => 'order'], 'data' => ['name' => '赵六', 'price' => 66.6]];
$operations[] = ['update' => ['_id' => '2', '_index' => 'order'], 'data' => ['doc' => ['price' => 188.8]]];
$operations[] = ['delete' => ['_id' => '3', '_index' => 'order']];

$results = OrderModel::query()->bulk($operations);
```

### 运行时映射字段（Runtime Fields）

```php
<?php
// 使用运行时字段进行查询
$result = OrderModel::query()->raw([
    'runtime_mappings' => [
        'price_with_tax' => [
            'type' => 'double',
            'script' => [
                'source' => 'emit(doc.price.value * 1.1)'
            ]
        ]
    ],
    'query' => [
        'match_all' => new \stdClass()
    ],
    'fields' => [
        'price_with_tax'
    ]
]);
```

### 跨索引查询

你可以在多个模型间切换：

```php
<?php
// 在OrderModel外定义ProductModel
$products = ProductModel::query()->get();
$orders = OrderModel::query()->get();
```

### 原生客户端访问

如果需要访问底层的Elasticsearch客户端，可以：

```php
<?php
// 获取原生客户端
$client = OrderModel::getClient();

// 使用原生客户端API
$response = $client->index([
    'index' => 'order',
    'id' => '1',
    'body' => [
        'name' => '测试',
        'price' => 100
    ]
]);
```

## 异常处理

```php
<?php
try {
    // ES操作
    $result = OrderModel::query()->get();
} catch (\Exception $e) {
    // 处理异常
    echo $e->getMessage();
}
```

## 高性能注意事项

1. 在协程环境中，该组件自动使用连接池提升性能
2. 可以通过配置文件调整连接池大小和超时设置
3. 对于大数据量操作，建议使用批量API而不是单条操作
4. 使用分页时，不要设置过大的页面大小

## 更多详情

有关更多高级功能和用法，请参考 Elasticsearch 官方文档：
[https://www.elastic.co/guide/en/elasticsearch/reference/current/index.html](https://www.elastic.co/guide/en/elasticsearch/reference/current/index.html)
