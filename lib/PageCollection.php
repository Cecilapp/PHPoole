<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole;

use ArrayIterator;
use Countable;
use DomainException;
//use InvalidArgumentException;
use IteratorAggregate;

class PageCollection implements Countable, IteratorAggregate
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

    public function add(Page $page)
    {
        $id = $page->getId();
        if (isset($this->pages[$id])) {
            throw new DomainException(sprintf(
                'Failed adding page "%s": a page with that id has already been added.',
                $id
            ));
        }
        $this->pages[$id] = $page;
        return $this;
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
     * Retrieve all pages IDs
     *
     * @return array
     */
    public function getIds()
    {
        return array_keys($this->pages);
    }

    public function replace($id, Page $page)
    {
        if ($this->has($id)) {
            $this->pages[$id] = $page;
        }
    }
}