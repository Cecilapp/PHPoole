<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Collection;

/**
 * Class AbstractItem.
 */
abstract class AbstractItem implements ItemInterface
{
    /**
     * Item identifier.
     *
     * @var string
     */
    protected $id;

    /**
     * AbstractItem constructor.
     *
     * @param null $id
     */
    public function __construct($id = null)
    {
        $this->setId($id);
    }

    /**
     * If parameter is empty uses the object hash
     * {@inheritdoc}
     */
    public function setId($id)
    {
        if (empty($id)) {
            $this->id = spl_object_hash($this);
        } else {
            $this->id = $id;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }
}
