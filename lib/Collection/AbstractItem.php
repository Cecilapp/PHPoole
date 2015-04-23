<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Collection;

/**
 * Class AbstractItem
 * @package PHPoole\Collection
 */
abstract class AbstractItem implements ItemInterface
{
    /**
     * @var string
     */
    protected $id;

    /**
     * Constructor
     *
     * Set an item identifier or the object hash by default
     *
     * @param null $id The item identifier
     */
    public function __construct($id = null)
    {
        if (empty($id)) {
            $this->id = spl_object_hash($this);
        } else {
            $this->id = $id;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->id;
    }
}
