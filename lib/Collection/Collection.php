<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Collection;

use DomainException;
use ArrayIterator;
use Closure;

/**
 * Class Collection
 * @package PHPoole\Collection
 */
abstract class Collection implements CollectionInterface
{
    /**
     * @var array
     */
    protected $items = array();

    /**
     * Constructor
     *
     * @param array $items
     */
    public function __construct($items = array())
    {
        $this->items = $items;
    }

    /**
     * {@inheritDoc}
     */
    public function has($id)
    {
        return array_key_exists($id, $this->items);
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
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
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function get($id)
    {
        if (!$this->has($id)) {
            return null;
        }
        return $this->items[$id];
    }

    /**
     * {@inheritDoc}
     */
    public function keys()
    {
        return array_keys($this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return $this->items;
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function filter(Closure $callback)
    {
        return new static(array_filter($this->items, $callback));
    }

    /**
     * {@inheritDoc}
     */
    public function map(Closure $callback)
    {
        return new static(array_map($callback, $this->items));
    }

    /**
     * Implement ArrayAccess
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Implement ArrayAccess
     *
     * @param mixed $offset
     * @return null
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Implement ArrayAccess
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->add($offset, $value);
    }

    /**
     * Implement ArrayAccess
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     * Implements usort
     *
     * @param Closure $callback
     */
    public function usort(Closure $callback)
    {
        usort($this->items, $callback);
    }

    /**
     * Returns a string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return __CLASS__ . '@' . spl_object_hash($this);
    }
}