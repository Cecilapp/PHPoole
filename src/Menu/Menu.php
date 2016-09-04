<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Menu;

use PHPoole\Collection\CollectionAbstract;
use PHPoole\Collection\ItemInterface;

/**
 * Class Menu.
 */
class Menu extends CollectionAbstract implements ItemInterface
{
    /**
     * Add menu entry.
     * {@inheritdoc}
     */
    public function add(ItemInterface $item)
    {
        $this->items[$item->getId()] = $item;

        return $this;
    }
}
