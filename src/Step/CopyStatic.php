<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Step;

use PHPoole\Util;
use Symfony\Component\Finder\Finder;

/**
 * Copy static directory content to site root.
 */
class CopyStatic extends AbstractStep
{
    /**
     * {@inheritdoc}
     *
     * @throws \PHPoole\Exception\Exception
     */
    public function internalProcess()
    {
        call_user_func_array($this->phpoole->getMessageCb(), ['COPY', 'Copy static files']);
        // copy theme static dir if exists
        if ($this->phpoole->getConfig()->hasTheme()) {
            $theme = $this->phpoole->getConfig()->get('theme');
            $themeStaticDir = $this->phpoole->getConfig()->getThemePath($theme, 'static');
            if (Util::getFS()->exists($themeStaticDir)) {
                Util::getFS()->mirror(
                    $themeStaticDir,
                    $this->phpoole->getConfig()->getOutputPath(),
                    null,
                    ['override' => true]
                );
            }
        }
        // copy static dir if exists
        $staticDir = $this->phpoole->getConfig()->getStaticPath();
        if (Util::getFS()->exists($staticDir)) {
            $finder = new Finder();
            $finder->files()->filter(function (\SplFileInfo $file) {
                return !(is_array($this->phpoole->getConfig()->get('static.exclude'))
                    && in_array($file->getBasename(), $this->phpoole->getConfig()->get('static.exclude')));
            })->in($staticDir);
            Util::getFS()->mirror(
                $staticDir,
                $this->phpoole->getConfig()->getOutputPath(),
                $finder,
                ['override' => true]
            );
        }
        call_user_func_array($this->phpoole->getMessageCb(), ['COPY_PROGRESS', 'Done']);
    }
}
