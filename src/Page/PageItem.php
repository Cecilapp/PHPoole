<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Page;

use PHPoole\Collection\AbstractItem;

/**
 * Class PageItem.
 */
class PageItem extends AbstractItem
{
    /**
     * Set variables.
     *
     * @param array $variables
     *
     * @return $this
     */
    public function setVariables($variables)
    {
        if (!is_array($variables)) {
            $variables = [];
        }
        $this->properties = array_merge_recursive($this->properties, $variables);

        return $this;
    }

    /**
     * Get variables.
     *
     * @return array
     */
    public function getVariables()
    {
        return $this->properties;
    }

    /**
     * Set a variable.
     *
     * @param $name
     * @param $value
     *
     * @return $this
     */
    public function setVariable($name, $value)
    {
        $this->offsetSet($name, $value);

        return $this;
    }

    /**
     * Is variable exist?
     *
     * @param $name
     *
     * @return bool
     */
    public function hasVariable($name)
    {
        return $this->offsetExists($name);
    }

    /**
     * Get a variable.
     *
     * @param $name
     *
     * @return mixed|boolean
     */
    public function getVariable($name)
    {
        if ($this->offsetExists($name)) {
            return $this->offsetGet($name);
        }

        return false;
    }

    /**
     * Unset a variable.
     *
     * @param $name
     */
    public function unVariable($name)
    {
        if ($this->offsetExists($name)) {
            $this->offsetUnset($name);
        }
    }
}