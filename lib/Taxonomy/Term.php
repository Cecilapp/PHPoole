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
 * Class Term
 * @package PHPoole\Taxonomy
 */
class Term extends AbstractCollection implements ItemInterface
{
    protected $name;

    /**
     * Create term
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Return term name
     *
     * @return string
     */
    public function getId()
    {
        return $this->name;
    }
}