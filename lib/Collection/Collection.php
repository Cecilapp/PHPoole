<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Collection;

use Countable;
use IteratorAggregate;
use ArrayIterator;
use DomainException;

/**
 * Class Collection
 * @package PHPoole\Collection
 */
abstract class Collection implements Countable, IteratorAggregate//, ArrayAccess
{
    /**
     * @var array
     */
    protected $items = array();

    /**
     * @param array $items
     */
    public function __construct($items = array())
    {
        $this->items = $items;
    }

    /**
     * Does the item exist?
     *
     * @param string $id
     * @return bool
     */
    public function has($id)
    {
        return array_key_exists($id, $this->items);
    }

    /**
     * Add an item
     *
     * @param ItemInterface $item
     * @return self
     */
    public function add(ItemInterface $item)
    {
        if ($this->has($item->getId())) {
            throw new DomainException(sprintf(
                'Failed adding item "%s": an item with that id has already been added.',
                $item->getId()
            ));
        }
        $this->items[$item->getId()] = $item;
        return $this;
    }

    /**
     * Replace an item if exists
     *
     * @param $id
     * @param ItemInterface $item
     */
    public function replace($id, ItemInterface $item)
    {
        if ($this->has($id)) {
            $this->items[$id] = $item;
        } else {
            throw new DomainException(sprintf(
                'Failed replacing item "%s": item does not exist.',
                $item->getId()
            ));
        }
    }

    /**
     * Remove an item if exists
     *
     * @param $id
     */
    public function remove($id)
    {
        if ($this->has($id)) {
            unset($this->items[$id]);
        } else {
            throw new DomainException(sprintf(
                'Failed removing item with ID "%s": item does not exist.',
                $id
            ));
        }
    }

    /**
     * Retrieve an item
     *
     * @param  string $id
     * @return null|self
     */
    public function get($id)
    {
        if (!$this->has($id)) {
            return null;
        }
        return $this->items[$id];
    }

    /**
     * Retrieve all the keys
     *
     * @return array An array of all the keys
     */
    public function keys()
    {
        return array_keys($this->items);
    }

    /**
     * Implement Countable
     *
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * Implement IteratorAggregate
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    public function toArray()
    {
        return $this->items;
    }

    /**
     * Implements usort
     *
     * @param callable $callback
     */
    public function usort(callable $callback)
    {
        usort($this->items, $callback);
    }

    /**
     * Filters items using a callback function
     *
     * @param callable $callback
     * @return Collection
     */
    public function filter(callable $callback)
    {
        return new static(array_filter($this->items, $callback));
    }

    /**
     * Applies a callback to items
     *
     * @param callable $callback
     * @return Collection
     */
    public function map(callable $callback)
    {
        return new static(array_map($callback, $this->items));
    }
}