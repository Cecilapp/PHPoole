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
 * Helper to set and get page variables.
 */
class PageItem extends AbstractItem
{
    /**
     * Set variables.
     *
     * @param array $variables
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function setVariables($variables)
    {
        if (!is_array($variables)) {
            throw new \Exception('Must be an array!');
        }
        //$this->properties = array_replace_recursive($this->properties, $variables);
        foreach ($variables as $key => $value) {
            $this->setVariable($key, $value);
        }

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
     * @throws \Exception
     *
     * @return $this
     */
    public function setVariable($name, $value)
    {
        switch ($name) {
            case 'date':
                try {
                    if (is_int($value)) {
                        $this->offsetSet('date', (new \DateTime())->setTimestamp($value));
                    } else {
                        $this->offsetSet('date', new \DateTime($value));
                    }
                } catch (\Exception $e) {
                    throw new \Exception(sprintf("Expected date string in page '%s'", $this->getName()));
                }
                break;
            default:
                $this->offsetSet($name, $value);
        }

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
     * @param string $name
     *
     * @return mixed|false
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
     *
     * @return $this
     */
    public function unVariable($name)
    {
        if ($this->offsetExists($name)) {
            $this->offsetUnset($name);
        }

        return $this;
    }
}
