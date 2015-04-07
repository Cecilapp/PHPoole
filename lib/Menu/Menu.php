<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Menu;

use PHPoole\Collection\AbstractCollection;
use PHPoole\Collection\ItemInterface;

/**
 * Class Menu
 * @package PHPoole\Menu
 */
class Menu extends AbstractCollection implements ItemInterface
{
    protected $name;

    /**
     * Set menu name
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Return menu name
     *
     * @return string
     */
    public function getId()
    {
        return $this->name;
    }

    /**
     * Add menu entry
     *
     * @param ItemInterface $item
     * @return self
     */
    public function add(ItemInterface $item)
    {
        $this->items[$item->getId()] = $item;
        return $this;
    }
}