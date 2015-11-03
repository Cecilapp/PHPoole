<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Plugin;

use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;

/**
 * Class AbstractPlugin.
 */
abstract class AbstractPlugin extends AbstractListenerAggregate implements PluginInterface
{
    /**
     * @var array
     */
    protected $handles = [];

    /**
     * {@inheritdoc}
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->handles as $handle) {
            $events->detach($handle);
        }
        $this->handles = [];

        return $this;
    }
}
