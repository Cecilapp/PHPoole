<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Collection;

/**
 * Implements \ArrayAccess
 *
 * Class ItemArrayAccessTrait
 * @package PHPoole\Collection
 */
trait ItemArrayAccessTrait
{
    /**
     * Item properties
     *
     * @var array
     */
    protected $properties = array();

    /**
     * Implement ArrayAccess
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->properties);
    }

    /**
     * Implement ArrayAccess
     *
     * @param mixed $offset
     * @return null
     */
    public function offsetGet($offset)
    {
        return $this->properties[$offset];
    }

    /**
     * Implement ArrayAccess
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->properties[$offset] = $value;
    }

    /**
     * Implement ArrayAccess
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->properties[$offset]);
    }
}
