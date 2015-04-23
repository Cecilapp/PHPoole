<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Menu;

use PHPoole\Collection\AbstractCollection;

/**
 * Class Collection
 * @package PHPoole\Menu
 */
class Collection extends AbstractCollection
{
    /**
     * Return menu (and create it if not exists)
     * {@inheritDoc}
     */
    public function get($id)
    {
        if (!$this->has($id)) {
            $this->add(new Menu($id));
        }
        return $this->items[$id];
    }
}
