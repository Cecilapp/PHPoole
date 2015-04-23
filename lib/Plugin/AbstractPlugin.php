<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Plugin;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\AbstractListenerAggregate;

/**
 * Class AbstractPlugin
 * @package PHPoole\Plugin
 */
abstract class AbstractPlugin extends AbstractListenerAggregate implements PluginInterface
{
    /**
     * @var array
     */
    protected $handles = array();

    /**
     * {@inheritdoc}
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->handles as $handle) {
            $events->detach($handle);
        }
        $this->handles = array();
        return $this;
    }
}
