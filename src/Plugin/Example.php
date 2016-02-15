<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Plugin;

use Zend\EventManager\Event;
use Zend\EventManager\EventManagerInterface;

class Example extends AbstractPlugin
{
    public function attach(EventManagerInterface $events)
    {
        $this->handles[] = $events->attach('options', [$this, 'onOptions']);
        $this->handles[] = $events->attach('locateContent.pre', [$this, 'onLocateContentPre']);
        $this->handles[] = $events->attach('locateContent.post', [$this, 'onLocateContentPost']);
        echo "Example plugin attached!\n";

        return $this;
    }

    public function onOptions(Event $event)
    {
        echo sprintf("- Method 'options' finished, with params '%s'\n", json_encode($event->getParams()));
    }

    public function onLocateContentPre(Event $event)
    {
        echo sprintf("- Method 'locateContent' started, with params '%s'\n", json_encode($event->getParams()));
    }

    public function onLocateContentPost(Event $event)
    {
        echo sprintf("- Method 'locateContent' finished, with params '%s'\n", json_encode($event->getParams()));
    }
}
