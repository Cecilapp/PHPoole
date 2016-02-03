<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Collection;

/**
 * Interface ItemInterface.
 */
interface ItemInterface
{
    /**
     * Set the item identifier.
     *
     * @param $id
     *
     * @return self
     */
    public function setId($id);

    /**
     * Return the item identifier.
     *
     * @return string
     */
    public function getId();
}
