<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Page;

use ArrayIterator;
use Countable;
use DomainException;
//use InvalidArgumentException;
use IteratorAggregate;

/**
 * Class Collection
 * @package PHPoole
 */
class Collection implements Countable, IteratorAggregate
{
    /**
     * @var array
     */
    protected $pages = array();

    /**
     * Implement Countable
     *
     * @return int
     */
    public function count()
    {
        return count($this->pages);
    }

    /**
     * Implement IteratorAggregate
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->pages);
    }

    /**
     * Does the page exist?
     *
     * @param string $id
     * @return bool
     */
    public function has($id)
    {
        return array_key_exists($id, $this->pages);
    }

    /**
     * Retrieve a page
     *
     * @param  string $id
     * @return null|self
     */
    public function get($id)
    {
        if (!$this->has($id)) {
            return null;
        }
        return $this->pages[$id];
    }

    /**
     * Add page
     *
     * @param Page $page
     * @return $this
     */
    public function add(Page $page)
    {
        if ($this->has($page->getId())) {
            throw new DomainException(sprintf(
                'Failed adding page "%s": a page with that id has already been added.',
                $page->getId()
            ));
        }
        $this->pages[$page->getId()] = $page;
        return $this;
    }

    /**
     * Retrieve all pages IDs
     *
     * @return array
     */
    public function getIds()
    {
        return array_keys($this->pages);
    }

    /**
     * Replace a page if exist
     *
     * @param $id
     * @param Page $page
     */
    public function replace($id, Page $page)
    {
        if ($this->has($id)) {
            $this->pages[$id] = $page;
        } else {
            throw new DomainException(sprintf(
                'Failed replacing page "%s": page does not exist.',
                $page->getId()
            ));
        }
    }

    /**
     * Implements usort
     *
     * @param callable $callback
     */
    public function usort(\Closure $callback)
    {
        usort($this->pages, $callback);
    }
}