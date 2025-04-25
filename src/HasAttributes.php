<?php

declare(strict_types=1);
/**
 * 属性.
 */

namespace Jhansin\Elasticsearch;

trait HasAttributes
{
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * The model's attributes.
     *
     * @var array
     */
    private $attributes = [];

    /**
     * The model attribute's original state.
     *
     * @var array
     */
    private $original = [];

    /**
     * Convert the model to its string representation.
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    public function getOriginal(): array
    {
        return $this->original;
    }

    public function setOriginal(array $original): void
    {
        $this->original = $original;
    }

    public function getCasts(): array
    {
        return $this->casts;
    }

    public function setCasts(array $casts): void
    {
        $this->casts = $casts;
    }

    public function toArray(): array
    {
        return $this->getAttributes();
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
     * The built-in, primitive cast types supported.
     *
     * @var array
     */
    //    protected static $primitiveCastTypes = [
    //        'array',
    //        'bool',
    //        'boolean',
    //        'collection',
    //        'custom_datetime',
    //        'date',
    //        'datetime',
    //        'decimal',
    //        'double',
    //        'float',
    //        'int',
    //        'integer',
    //        'json',
    //        'object',
    //        'real',
    //        'string',
    //        'timestamp',
    //    ];
    protected function initData(): void
    {
        $this->setOriginal([]);
        $this->setAttributes([]);
    }
}
