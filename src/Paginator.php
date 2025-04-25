<?php

declare(strict_types=1);

namespace Jhansin\Elasticsearch;

use Hyperf\Collection\Collection;
use Hyperf\Contract\Arrayable;
use Hyperf\Contract\Jsonable;
use JsonSerializable;

class Paginator implements Arrayable, JsonSerializable, Jsonable
{
    /**
     * Determine if there are more items in the data source.
     *
     * @return bool
     */
    protected $hasMore;

    protected $perPage;

    protected $currentPage;

    protected $total;

    protected $items;

    /**
     * Create a new paginator instance.
     *
     * @param mixed $items
     */
    public function __construct($items, int $perPage, int $currentPage, int $total)
    {
        $this->items = $items;
        $this->perPage = $perPage;
        $this->currentPage = $currentPage;
        $this->total = $total;
        $this->setItems($items);
    }

    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * Determine if there are more items in the data source.
     */
    public function hasMorePages(): bool
    {
        return $this->hasMore;
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'page' => $this->currentPage,
            'size' => $this->perPage,
            'data' => $this->items->toArray(),
        ];
    }

    /**
     * Convert the object into something JSON serializable.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Convert the object to its JSON representation.
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Set the items for the paginator.
     * @param mixed $items
     */
    protected function setItems($items): void
    {
        $this->items = $items instanceof Collection ? $items : Collection::make($items);

        $this->hasMore = $this->items->count() > $this->perPage;

        $this->items = $this->items->slice(0, $this->perPage);
    }
}
