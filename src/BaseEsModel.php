<?php

declare(strict_types=1);

namespace Jhansin\Elasticsearch;

use Hyperf\Collection\Collection;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\Arrayable;
use Hyperf\Contract\Jsonable;
use JsonSerializable;

use function Hyperf\Support\call;

abstract class BaseEsModel implements Arrayable, Jsonable, JsonSerializable
{
    use HasAttributes;

    /**
     * @var string 索引
     */
    protected $index;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $connection = 'default';

    public function __construct()
    {
        $this->client = ApplicationContext::getContainer()->get(Client::class);
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param string $method
     * @param array $parameters
     */
    public function __call($method, $parameters)
    {
        return call([$this->newQuery(), $method], $parameters);
    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param string $method
     * @param array $parameters
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static())->{$method}(...$parameters);
    }

    /**
     * @return Builder
     */
    public static function query()
    {
        return (new static())->newQuery();
    }

    /**
     * @return Builder
     */
    public function newQuery()
    {
        return $this->newModelBuilder()->setModel($this);
    }

    /**
     * @return \Elasticsearch\Client
     */
    public function getClient()
    {
        return $this->client->create($this->connection);
    }

    /**
     * Create a new Model Collection instance.
     *
     * @return Collection
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }

    /**
     * @return $this
     */
    public function newInstance()
    {
        $model = new static();
        return $model;
    }

    /**
     * Create a new Model query builder.
     *
     * @return Builder
     */
    public function newModelBuilder()
    {
        return new Builder();
    }

    public function getIndex(): string
    {
        return $this->index;
    }

    public function setIndex(string $index): void
    {
        $this->index = $index;
    }
}
