<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Taxonomy;

use PHPoole\Collection\AbstractCollection;
use PHPoole\Collection\ItemInterface;

/**
 * Class Vocabulary
 * @package PHPoole\Taxonomy
 */
class Vocabulary extends AbstractCollection implements ItemInterface
{
    protected $name;

    /**
     * Create vocabulary
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Return vocabulary name
     *
     * @return string
     */
    public function getId()
    {
        return $this->name;
    }

    /**
     * Adds term to vocabulary
     *
     * @param ItemInterface $item
     * @return $this
     */
    public function add(ItemInterface $item)
    {
        if ($this->has($item->getId())) {
            // return if already exists
            return $this;
        }
        $this->items[$item->getId()] = $item;
        return $this;
    }
}
