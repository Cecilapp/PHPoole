<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Step;

use PHPoole\PHPoole;

abstract class AbstractStep implements StepInterface
{
    protected $phpoole;
    protected $process = false;

    /**
     * {@inheritdoc}
     */
    public function __construct(PHPoole $phpoole)
    {
        $this->phpoole = $phpoole;
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
    public function process()
    {
        if ($this->process) {
            $this->internalProcess();
        }
    }

    /**
     * {@inheritdoc}
     */
    abstract function internalProcess();
}
