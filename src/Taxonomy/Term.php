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
 * Class Term.
 */
class Term extends AbstractCollection implements ItemInterface
{
    protected $name;

    /**
     * Set term name.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setId($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Return term name.
     *
     * @return string
     */
    public function getId()
    {
        return $this->name;
    }
}
