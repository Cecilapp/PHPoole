<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Plugin;

use Zend\EventManager\EventManager;

trait PluginAwareTrait
{
    /**
     * The EventManager.
     *
     * @var null|EventManager
     */
    protected $events = null;
    /**
     * The plugin registry.
     *
     * @var \SplObjectStorage
     */
    protected $pluginRegistry;

    /**
     * Get the event manager.
     *
     * @return EventManager
     */
    public function getEventManager()
    {
        if ($this->events === null) {
            $this->events = new EventManager([__CLASS__, get_class($this)]);
        }

        return $this->events;
    }

    /**
     * Trigger event.
     *
     * @param string $eventName
     * @param array  $params
     */
    protected function trigger($eventName, array $params = [])
    {
        $params = $this->getEventManager()->prepareArgs($params);
        $this->getEventManager()->trigger($eventName, $this, $params);
    }

    /**
     * Trigger "pre" event.
     *
     * @param string $eventName
     * @param array  $params
     *
     * @see   trigger()
     */
    protected function triggerPre($eventName, array $params = [])
    {
        $this->trigger($eventName.'.pre', $params);
    }

    /**
     * Trigger "post" event.
     *
     * @param string $eventName
     * @param array  $params
     *
     * @see   trigger()
     */
    protected function triggerPost($eventName, array $params = [])
    {
        $this->trigger($eventName.'.post', $params);
    }

    /**
     * Trigger "exception" event.
     *
     * @param string $eventName
     * @param array  $params
     *
     * @see   trigger()
     */
    protected function triggerException($eventName, array $params = [])
    {
        $this->trigger($eventName.'.exception', $params);
    }

    /**
     * Check if a plugin is registered.
     *
     * @param PluginInterface $plugin
     *
     * @return bool
     */
    public function hasPlugin(PluginInterface $plugin)
    {
        $registry = $this->getPluginRegistry();

        return $registry->contains($plugin);
    }

    /**
     * Register a plugin.
     *
     * @param PluginInterface $plugin
     * @param int             $priority
     *
     * @throws \LogicException
     *
     * @return self
     */
    public function addPlugin(PluginInterface $plugin, $priority = 1)
    {
        $registry = $this->getPluginRegistry();
        if ($registry->contains($plugin)) {
            throw new \LogicException(sprintf(
                'Plugin of type "%s" already registered',
                get_class($plugin)
            ));
        }
        $plugin->attach($this->getEventManager(), $priority);
        $registry->attach($plugin);

        return $this;
    }

    /**
     * Remove an already registered plugin.
     *
     * @param PluginInterface $plugin
     *
     * @throws \LogicException
     *
     * @return self
     */
    public function removePlugin(PluginInterface $plugin)
    {
        $registry = $this->getPluginRegistry();
        if ($registry->contains($plugin)) {
            $plugin->detach($this->getEventManager());
            $registry->detach($plugin);
        }

        return $this;
    }

    /**
     * Return registry of plugins.
     *
     * @return \SplObjectStorage
     */
    public function getPluginRegistry()
    {
        if (!$this->pluginRegistry instanceof \SplObjectStorage) {
            $this->pluginRegistry = new \SplObjectStorage();
        }

        return $this->pluginRegistry;
    }
}
