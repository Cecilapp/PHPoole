<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Step;

use PHPoole\Config;
use PHPoole\PHPoole;

abstract class AbstractStep implements StepInterface
{
    /**
     * @var PHPoole
     */
    protected $phpoole;
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var bool
     */
    protected $process = false;

    /**
     * {@inheritdoc}
     */
    public function __construct(PHPoole $phpoole)
    {
        $this->phpoole = $phpoole;
        $this->config = $phpoole->getConfig();
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->process = true;
    }

    /**
     * {@inheritdoc}
     */
    public function runProcess()
    {
        if ($this->process) {
            $this->process();
        }
    }

    /**
     * {@inheritdoc}
     */
    abstract public function process();
}
