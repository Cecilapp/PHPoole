<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Step;

use PHPoole\Exception\Exception;
use PHPoole\Page\Page;
use PHPoole\PHPoole;
use PHPoole\Renderer\Layout;
use PHPoole\Renderer\Twig as Twig;
use PHPoole\Util;

/**
 * Pages rendering:
 * 1. Iterates Pages collection
 * 2. Applies Twig templates
 * 3. Saves rendered files.
 */
class RenderPages extends AbstractStep
{
    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function init()
    {
        // checks layouts dir
        if (!is_dir($this->phpoole->getConfig()->getLayoutsPath()) && !$this->phpoole->getConfig()->hasTheme()) {
            throw new Exception(sprintf(
                "'%s' is not a valid layouts directory", $this->phpoole->getConfig()->getLayoutsPath()
            ));
        }
        $this->process = true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function internalProcess()
    {
        $paths = [];

        // prepares renderer
        if (is_dir($this->phpoole->getConfig()->getLayoutsPath())) {
            $paths[] = $this->phpoole->getConfig()->getLayoutsPath();
        }
        if ($this->phpoole->getConfig()->hasTheme()) {
            $paths[] = $this->phpoole->getConfig()->getThemePath($this->phpoole->getConfig()->get('theme'));
        }
        $this->phpoole->setRenderer(new Twig($paths, $this->phpoole->getConfig()));

        // adds global variables
        $this->phpoole->getRenderer()->addGlobal('site', array_merge(
            $this->phpoole->getConfig()->get('site'),
            ['menus' => $this->phpoole->getMenus()],
            ['pages' => $this->phpoole->getPages()]
        ));
        $this->phpoole->getRenderer()->addGlobal('phpoole', [
            'url'       => 'http://phpoole.org/#v'.$this->phpoole->getVersion(),
            'version'   => $this->phpoole->getVersion(),
            'poweredby' => 'PHPoole-library v'.$this->phpoole->getVersion(),
        ]);

        // start rendering
        Util::getFS()->mkdir($this->phpoole->getConfig()->getOutputPath());
        call_user_func_array($this->phpoole->getMessageCb(), ['RENDER', 'Rendering pages']);
        $max = count($this->phpoole->getPages());
        $count = 0;
        /* @var $page Page */
        foreach ($this->phpoole->getPages() as $page) {
            $count++;
            $pathname = $this->renderPage($page, $this->phpoole->getConfig()->getOutputPath());
            $message = substr($pathname, strlen($this->phpoole->getConfig()->getDestinationDir()) + 1);
            call_user_func_array($this->phpoole->getMessageCb(), ['RENDER_PROGRESS', $message, $count, $max]);
        }
    }

    /**
     * Render a page.
     *
     * @param Page   $page
     * @param string $dir
     *
     * @throws Exception
     *
     * @see renderPages()
     *
     * @return string Path to the generated page
     */
    protected function renderPage(Page $page, $dir)
    {
        $this->phpoole->getRenderer()->render(
            (new Layout())->finder($page, $this->phpoole->getConfig()),
            ['page' => $page]
        );

        // force pathname of a (non virtual) node page
        if ($page->getName() == 'index') {
            $pathname = $dir.'/'.$page->getPath().'/'.$this->phpoole->getConfig()->get('output.filename');
            // pathname of a (normal) page
        } else {
            if (empty(pathinfo($page->getPermalink(), PATHINFO_EXTENSION))) {
                $pathname = $dir.'/'.$page->getPermalink().'/'.$this->phpoole->getConfig()->get('output.filename');
            } else {
                $pathname = $dir.'/'.$page->getPermalink();
            }
        }
        // remove unnecessary slashes
        $pathname = preg_replace('#/+#', '/', $pathname);

        $this->phpoole->getRenderer()->save($pathname);

        return $pathname;
    }
}
