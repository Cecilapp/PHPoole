<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Collection;

use ArrayIterator;
use Closure;
use DomainException;

/**
 * Class AbstractCollection.
 */
abstract class AbstractCollection implements CollectionInterface
{
    /**
     * @var array
     */
    protected $items = [];

    /**
     * Constructor.
     *
     * @param array $items
     */
    public function __construct($items = [])
    {
        $this->items = $items;
    }

    /**
     * {@inheritdoc}
     */
    public function has($id)
    {
        return array_key_exists($id, $this->items);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function get($id)
    {
        if (!$this->has($id)) {
            return;
        }

        return $this->items[$id];
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        return array_keys($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->items;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function usort(Closure $callback = null)
    {
        $items = $this->items;
        $callback ? uasort($items, $callback) : uasort($items, function ($a, $b) {
            if ($a == $b) {
                return 0;
            }

            return ($a < $b) ? -1 : 1;
        });

        return new static($items);
    }

    /**
     * Sort items by date.
     *
     * @return AbstractCollection|CollectionInterface|static
     */
    public function sortByDate()
    {
        return $this->usort(function ($a, $b) {
            if (!isset($a['date'])) {
                return -1;
            }
            if (!isset($b['date'])) {
                return 1;
            }
            if ($a['date'] == $b['date']) {
                return 0;
            }

            return ($a['date'] > $b['date']) ? -1 : 1;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function filter(Closure $callback)
    {
        return new static(array_filter($this->items, $callback));
    }

    /**
     * {@inheritdoc}
     */
    public function map(Closure $callback)
    {
        return new static(array_map($callback, $this->items));
    }

    /**
     * Implement ArrayAccess.
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Implement ArrayAccess.
     *
     * @param mixed $offset
     *
     * @return null|CollectionInterface
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Implement ArrayAccess.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->add($value);
    }

    /**
     * Implement ArrayAccess.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     * Returns a string representation of this object.
     *
     * @return string
     */
    public function __toString()
    {
        return __CLASS__.'@'.spl_object_hash($this);
    }
}
