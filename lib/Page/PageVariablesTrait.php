<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Page;

/**
 * Class PageVariablesTrait
 * @package PHPoole\Page
 */
trait PageVariablesTrait
{
    /**
     * @var array
     */
    protected $variables = [];

    /**
     * Set variables
     *
     * @param $variables
     * @return $this
     */
    public function setVariables($variables)
    {
        $this->variables = $variables;
        return $this;
    }

    /**
     * Get variables
     *
     * @return array
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * Set a variable
     *
     * @param $name
     * @param $value
     * @return $this
     */
    public function setVariable($name, $value)
    {
        $this->variables[$name] = $value;
        return $this;
    }

    /**
     * Is variable exist?
     *
     * @param $name
     * @return bool
     */
    public function hasVariable($name)
    {
        return array_key_exists($name, $this->variables);
    }

    /**
     * Get a variable
     *
     * @param $name
     * @return null
     */
    public function getVariable($name)
    {
        if ($this->hasVariable($name)) {
            return $this->variables[$name];
        }
        return null;
    }

    /**
     * Unset a variable
     *
     * @param $name
     */
    public function unVariable($name)
    {
        if ($this->hasVariable($name)) {
            unset($this->variables[$name]);
        }
    }

    /**
     * Implement ArrayAccess
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->hasVariable($offset);
    }

    /**
     * Implement ArrayAccess
     *
     * @param mixed $offset
     * @return null
     */
    public function offsetGet($offset)
    {
        return $this->getVariable($offset);
    }

    /**
     * Implement ArrayAccess
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->setVariable($offset, $value);
    }

    /**
     * Implement ArrayAccess
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->unVariable($offset);
    }
}
