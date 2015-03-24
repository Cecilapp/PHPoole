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
use ArrayAccess;
use Closure;

/**
 * Interface CollectionInterface
 * @package PHPoole\Collection
 */
interface CollectionInterface extends Countable, IteratorAggregate, ArrayAccess
{
    /**
     * Does the item exist?
     *
     * @param string $id
     * @return bool
     */
    public function has($id);

    /**
     * Add an item
     *
     * @param ItemInterface $item
     * @return self
     */
    public function add(ItemInterface $item);

    /**
     * Replace an item if exists
     *
     * @param $id
     * @param ItemInterface $item
     */
    public function replace($id, ItemInterface $item);

    /**
     * Remove an item if exists
     *
     * @param $id
     */
    public function remove($id);

    /**
     * Retrieve an item
     *
     * @param  string $id
     * @return null|self
     */
    public function get($id);

    /**
     * Retrieve all the keys
     *
     * @return array An array of all the keys
     */
    public function keys();

    /**
     * Implement Countable
     *
     * @return int
     */
    public function count();

    /**
     * Return collection
     *
     * @return array
     */
    public function toArray();

    /**
     * Implement IteratorAggregate
     *
     * @return ArrayIterator
     */
    public function getIterator();

    /**
     * Filters items using a callback function
     *
     * @param Closure $callback
     * @return Collection
     */
    public function filter(Closure $callback);

    /**
     * Applies a callback to items
     *
     * @param Closure $callback
     * @return Collection
     */
    public function map(Closure $callback);
}