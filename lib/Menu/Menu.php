<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Menu;

use PHPoole\Collection\Collection as AbstractCollecton;
use PHPoole\Collection\ItemInterface;

/**
 * Class Menu
 * @package PHPoole\Menu
 */
class Menu extends AbstractCollecton implements ItemInterface
{
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getId()
    {
        return $this->name;
    }
}