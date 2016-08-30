<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Steps;

use PHPoole\Exception\Exception;
use PHPoole\PHPoole;
use Symfony\Component\Finder\Finder;

/**
 * Locates content.
 */
class LocateContent implements StepInterface
{
    protected $phpoole;

    public function __construct(PHPoole $PHPoole)
    {
        $this->phpoole = $PHPoole;
    }

    public function init()
    {
        if (!is_dir($this->phpoole->config->getContentPath())) {
            throw new Exception(sprintf('%s not found!', $this->phpoole->config->getContentPath()));
        }
    }

    public function process()
    {
        try {
            $this->phpoole->content = Finder::create()
                ->files()
                ->in($this->phpoole->config->getContentPath())
                ->name('/\.('.implode('|', $this->phpoole->config->get('content.ext')).')$/');
            if (!$this->phpoole->content instanceof Finder) {
                throw new Exception(__FUNCTION__.': result must be an instance of Symfony\Component\Finder.');
            }
        } catch (Exception $e) {
            echo $e->getMessage()."\n";
        }
    }
}
