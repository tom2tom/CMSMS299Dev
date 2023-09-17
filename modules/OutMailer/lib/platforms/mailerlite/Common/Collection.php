<?php

namespace MailerLiteApi\Common;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;

class Collection implements ArrayAccess, IteratorAggregate, Countable {

    /**
     * The items contained in the collection.
     *
     * @var array
     */
    protected $items = [];

    /**
     * Create a new collection.
     *
     * @param  array $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Create a new collection instance if the value isn't one already.
     *
     * @param  mixed $items
     *
     * @return static
     */
    public static function make($items)
    {
        if (is_null($items)) {
            return new static;
        }

        if ($items instanceof Collection) {
            return $items;
        }

        return new static(is_array($items) ? $items : [$items]);
    }

    /**
     * Get the first item from the collection.
     *
     * @return mixed|null
     */
    public function first()
    {
        return count($this->items) > 0 ? reset($this->items) : null;
    }

    /**
     * Get the collection of items as a plain array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_map(
            function ($value) {
                return $value instanceof Entity ? $value->toArray() : $value;

            }, $this->items
        );
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param  string $key
     *
     * @return bool
     */
    public function offsetExists(/*mixed */$key): bool
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Get an item at a given offset.
     *
     * @param  string $key
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet(/*mixed */$key)//: mixed
    {
        return $this->items[$key];
    }

    /**
     * Set the item at a given offset.
     *
     * @param  string $key
     * @param  mixed $value
     *
     * @return void
     */
    public function offsetSet(/*mixed */$key, $value): void
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    /**
     * Unset the item at a given offset.
     *
     * @param  string $key
     *
     * @return void
     */
    public function offsetUnset(/*mixed */$key): void
    {
        unset($this->items[$key]);
    }

}