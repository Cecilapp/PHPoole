<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Renderer;

use Symfony\Component\Filesystem\Filesystem;

class Twig implements RendererInterface
{
    protected $twig;
    protected $templates_dir = 'templates';
    protected $rendered;
    protected $filesystem;
    //protected $cache_dir = 'cache';

    public function __construct($templatesPath=null)
    {
        if ($templatesPath != null) {
            $this->templates_dir = $templatesPath;
        }
        $this->twig = new \Twig_Environment(
            new \Twig_Loader_Filesystem($this->templates_dir),
            [
                //'strict_variables' => true,
                'autoescape' => false,
                'debug'      => true
            ]
        );
        $this->twig->addExtension(new \Twig_Extension_Debug());
        $this->twig->addExtension(new TwigExtensionSortArray());
        $this->filesystem = new Filesystem();
        // cache
        //if (!$this->filesystem->exists($this->cache_dir . '/twig')) {
        //    $this->filesystem->mkdir($this->cache_dir . '/twig');
        //}
        //$this->twig->setCache($this->cache_dir . '/twig');
    }

    public function addPath($path)
    {
        if (is_dir($path)) {
            /* @var $loader \Twig_Loader_Filesystem */
            $loader = $this->twig->getLoader();
            $loader->addPath($path);
            $this->twig->setLoader($loader);
        }
    }

    public function render($template, $variables)
    {
        $this->rendered = $this->twig->render($template, $variables);
        return $this;
    }

    public function debug()
    {
        echo $this->rendered . "\n";
    }

    public function save($pathname)
    {
        if (!is_dir($dir = dirname($pathname))) {
            $this->filesystem->mkdir($dir);
        }
        file_put_contents($pathname, $this->rendered);
    }
}