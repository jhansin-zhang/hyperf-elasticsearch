<?php

declare(strict_types=1);

namespace Jhansin\Elasticsearch;

use Hyperf\Collection\Arr;
use Hyperf\Collection\Collection;
use stdClass;

use function Hyperf\Collection\collect;
use function Hyperf\Support\call;
use function Hyperf\Support\make;

class Builder
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * The base query.
     *
     * @var []
     */
    protected $query;

    /**
     * @var array
     */
    protected $highlight = [];

    protected $sql;

    /**
     * @var []
     */
    protected $sort = [];

    /**
     * The model being queried.
     *
     * @var BaseEsModel
     */
    protected $model;

    /**
     * @var int
     */
    protected $take = 50;

    /**
     * @var array
     */
    protected $operate = [
        '=',
        '>',
        '<',
        '>=',
        '<=',
        '!=',
        '<>',
        'in',
        'not in',
        'like',
        'regex',
        'prefix',
    ];

    protected $source = ['*'];

    protected $total = 0;

    /**
     * 分页.
     */
    public function page(int $size = 50, int $page = 1): Paginator
    {
        $from = (($page - 1) * $size);
        $this->sql = [
            'from' => $from,
            'size' => $size,
            'index' => $this->model->getIndex(),
            'body' => [
                'query' => $this->query,
                // 'highlight' => $this->highlight,
                'sort' => $this->sort,
            ],
        ];

        if (empty($this->query)) {
            $this->sql = [
                'from' => $from,
                'size' => $size,
                'index' => $this->model->getIndex(),
                'body' => [
                    'query' => [
                        'match_all' => new stdClass(),
                    ],
                ],
            ];
        }
        if (!in_array('*', $this->source)) {
            $this->sql['_source'] = $this->source;
        }
        $result = $this->run('search', $this->sql);
        $original = $result['hits']['hits'] ?? [];
        $total = $result['hits']['total']['value'];
        $collection = Collection::make($original)->map(function ($value) {
            $attributes = $value['_source'] ?? [];
            $model = $this->model->newInstance();
            $model->setAttributes($attributes);
            $model->setOriginal($value);
            return $model;
        });
        return make(Paginator::class, ['items' => $collection, 'perPage' => $size, 'currentPage' => $page, 'total' => $total]);
    }

    public function get(): Collection
    {
        $this->sql = [
            'size' => 9999,
            'index' => $this->model->getIndex(),
            'body' => [
                'query' => $this->query,
                // 'highlight' => $this->highlight,
                'sort' => $this->sort,
            ],
        ];

        if (empty($this->query)) {
            $this->sql = [
                'size' => 9999,
                'index' => $this->model->getIndex(),
                'body' => [
                    'query' => [
                        'match_all' => new stdClass(),
                    ],
                ],
            ];
        }

        if (!in_array('*', $this->source)) {
            $this->sql['_source'] = $this->source;
        }
        $result = $this->run('search', $this->sql);
        // var_dump($this->sql);die;
        $original = $result['hits']['hits'] ?? [];

        return Collection::make($original)->map(function ($value) {
            $attributes = $value['_source'] ?? [];
            $model = $this->model->newInstance();
            $model->setAttributes($attributes);
            $model->setOriginal($value);
            return $model;
        });
    }

    public function groupBy($aggs = [])
    {
        $this->sql = [
            'size' => 0,
            'index' => $this->model->getIndex(),
            'body' => [
                'query' => $this->query,
                'aggs' => $aggs,
            ],
        ];
        return $this->run('search', $this->sql);
    }

    public function first(): BaseEsModel
    {
        return $this->take(1)->get()->first();
    }

    public function take(int $take): Builder
    {
        $this->take = $take;
        return $this;
    }

    /**
     * 查找单条
     * @param mixed $id
     * @return array|BaseEsModel
     */
    public function find($id)
    {
        $this->sql = [
            'index' => $this->model->getIndex(),
            'id' => $id,
        ];
        $result = $this->run('get', $this->sql);

        $this->model->setAttributes($result['_source'] ?? []);
        $this->model->setOriginal($result);
        return $this->model;
    }

    /**
     * insert.
     */
    public function insert(array $values): Collection
    {
        $body = [];
        foreach ($values as $value) {
            $body['body'][] = [
                'index' => [
                    '_index' => $this->model->getIndex(),
                ],
            ];
            $body['body'][] = $value;
        }
        $this->sql = $body;
        $result = $this->run('bulk', $this->sql);
        return collect($result['items'])->map(function ($value, $key) use ($values) {
            $this->model->setAttributes(Arr::merge($values[$key] ?? [], ['_id' => $values['index']['_id'] ?? '']));
            $this->model->setOriginal($value);
            return $this->model;
        });
    }

    /**
     * insert.
     *
     * @return BaseEsModel|Collection
     */
    public function create(array $value)
    {
        $body = Arr::except($value, ['id', 'routing', 'timestamp']);
        $except = Arr::only($value, ['id', 'routing', 'timestamp']);
        $this->sql = Arr::merge($except, [
            'index' => $this->model->getIndex(),
            'body' => $body,
        ]);
        $result = $this->run('index', $this->sql);
        if (!empty($data['result']) && $data['result'] == 'created') {
            $this->model->setOriginal($result);
            $this->model->setAttributes(Arr::merge($body, ['_id' => $result['_id'] ?? '']));
        }
        return $this->model;
    }

    /**
     * update.
     *
     * @param string $id
     *
     * @return BaseEsModel
     */
    public function update(array $value, $id)
    {
        $result = $this->run('update', [
            [
                'index' => $this->model->getIndex(),
                'id' => $id,
                'body' => [
                    'doc' => $value,
                ],
            ],
        ]);
        if (!empty($result['result']) && ($result['result'] == 'updated' || $result['result'] == 'noop')) {
            $this->model->setOriginal($result);
            $this->model->setAttributes(['_id' => $result['_id'] ?? '']);
        }
        return $this->model;
    }

    /**
     * delete.
     */
    public function delete(string $id): bool
    {
        $result = $this->run('delete', [
            [
                'index' => $this->model->getIndex(),
                'id' => $id,
            ],
        ]);
        if (!empty($result['result']) && $result['result'] == 'deleted') {
            return true;
        }
        return false;
    }

    public function updateMapping(array $mappings)
    {
        $mappings = collect($mappings)->map(function ($value, $key) {
            $valued = [];
            if (is_string($value)) {
                $valued['type'] = $value;
            }
            if (is_array($value)) {
                $valued = $value;
            }
            return $valued;
        })->toArray();
        $this->sql = [
            'index' => $this->model->getIndex(),
            'body' => [
                'properties' => $mappings,
            ],
        ];
        return $this->run('indices.putMapping', $this->sql);
    }

    public function updateSetting(array $settings)
    {
        $this->sql = [
            'index' => $this->model->getIndex(),
            'body' => [
                'settings' => $settings,
            ],
        ];
        return $this->run('putSettings', $this->sql);
    }

    public function createIndex(array $mappings = [], array $settings = [])
    {
        $mappings = Arr::merge(
            Collection::make($this->model->getCasts())->map(function ($value, $key) {
                $valued = '';
                if (is_string($value)) {
                    $valued['type'] = $value;
                }
                if (is_array($value)) {
                    $valued = $value;
                }
                return $valued;
            })->toArray(),
            Collection::make($mappings)->map(function ($value, $key) {
                $valued = '';
                if (is_string($value)) {
                    $valued['type'] = $value;
                }
                if (is_array($value)) {
                    $valued = $value;
                }
                return $valued;
            })->toArray()
        );
        $this->sql = [
            'index' => $this->model->getIndex(),
            'id' => '1',
            'body' => compact('mappings') + compact('settings'),
        ];
        // Create the index
        return $this->run('indices.index', $this->sql);
    }

    public function select(array $fields)
    {
        $this->source = $fields;
        return $this;
    }

    public function matchPhrase(string $field, $value)
    {
        $this->query['match_phrase'][$field] = $value;
        return $this;
    }

    public function match(string $field, $value)
    {
        $this->query['match'][$field] = $value;
        return $this;
    }

    /**
     * @param null $value
     */
    public function where(string $field, string $operate, $value = null): Builder
    {
        if (is_null($value)) {
            $value = $operate;
            $operate = '=';
        }
        if (in_array($operate, $this->operate)) {
            $this->parseQuery($field, $operate, $value);
        }
        return $this;
    }

    public function whereIn(string $field, $value): Builder
    {
        return $this->where($field, 'in', $value);
    }

    public function whereNotIn(string $field, $value): Builder
    {
        return $this->where($field, 'not in', $value);
    }

    public function whereLike(string $field, $value): Builder
    {
        return $this->where($field, 'like', $value);
    }

    public function highlight($fields)
    {
        $fields = (array)$fields;

        $fields = Collection::make($fields)
            ->map(function ($item) {
                return [
                    $item => new stdClass(),
                ];
            })->toArray();
        $this->highlight = compact('fields');
        return $this;
    }

    /**
     * orderBy.
     */
    public function orderBy(string $field, bool $desc = false): Builder
    {
        $sort = $desc ? 'desc' : 'asc';
        if (!is_array($this->sort)) {
            $this->sort = [];
        }
        $this->sort[] = [$field => ['order' => $sort]];

        return $this;
    }

    /**
     * Set a model instance for the model being queried.
     *
     * @return $this
     */
    public function setModel(BaseEsModel $model)
    {
        $this->model = $model;
        $this->client = $model->getClient();
        $this->highlight = new stdClass();
        $this->sort = new stdClass();
        return $this;
    }

    public function should(string $field, string $operate, $value = null): Builder
    {
        if (is_null($value)) {
            $value = $operate;
            $operate = '=';
        }
        if (in_array($operate, $this->operate)) {
            $this->parseQuery($field, $operate, $value, 'should');
        }
        return $this;
    }

    /**
     * parseWhere.
     *
     * @param mixed $value
     * @param mixed $type
     * @return array
     */
    protected function parseQuery(string $field, string $operate, $value, $type = 'must'): Builder
    {
        switch ($operate) {
            case '=':
                $result = ['match' => [$field => $value]];
                break;
            case '>':
                $result = ['range' => [$field => ['gt' => $value]]];
                break;
            case '<':
                $result = ['range' => [$field => ['lt' => $value]]];
                break;
            case '>=':
                $result = ['range' => [$field => ['gte' => $value]]];
                break;
            case '<=':
                $result = ['range' => [$field => ['lte' => $value]]];
                break;
            case '<>':
            case '!=':
                $type = 'must_not';
                $result = ['match' => [$field => $value]];
                break;
            case 'in':
                $result = ['terms' => [$field => $value]];
                break;
            case 'not in':
                $type = 'must_not';
                $result = ['terms' => [$field => $value]];
                break;
            case 'like':
                $result = ['wildcard' => [$field => '*' . $value . '*']];
                break;
            case 'regex':
                $result = ['regexp' => [$field => $value]];
                break;
            case 'prefix':
                $result = ['prefix' => [$field => $value]];
                break;
        }
        $this->query['bool'][$type][] = $result;
        if ($type == 'should') {
            $this->query['bool']['minimum_should_match'] = 1;
        }
        return $this;
    }

    protected function run($method, ...$parameters)
    {
        $client = $this->client;
        $sql = $this->sql;
        if (strpos($method, '.')) {
            $methods = explode('.', $method);
            $method = $methods[1];
            $client = $client->{$methods[0]}();
        }
        return call([$client, $method], $parameters);
    }
}
