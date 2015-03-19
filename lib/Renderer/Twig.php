<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Renderer;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Class Twig
 * @package PHPoole\Renderer
 */
class Twig implements RendererInterface
{
    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var string
     */
    protected $templates_dir;

    /**
     * @var string
     */
    protected $rendered;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * {@inheritdoc}
     */
    public function __construct($templatesPath = null)
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
        $this->twig->addExtension(new TwigExtensionUrlize());

        // excerpt filter
        $excerptFilter = new \Twig_SimpleFilter('excerpt', function ($string, $length = 450, $suffix = '…') {
            $str = trim(strip_tags($string));
            if (mb_strlen($str) > $length) {
                $string = mb_substr($string, 0, $length) . $suffix;
            }
            return $string;
        });
        $this->twig->addFilter($excerptFilter);

        $this->filesystem = new Filesystem();
    }

    /**
     * Add templates path
     *
     * @param $path
     * @throws \Twig_Error_Loader
     */
    public function addPath($path)
    {
        if (is_dir($path)) {
            /* @var $loader \Twig_Loader_Filesystem */
            $loader = $this->twig->getLoader();
            $loader->addPath($path);
            $this->twig->setLoader($loader);
        }
    }

    /**
     * Add global variable
     *
     * @param $name
     * @param $value
     */
    public function addGlobal($name, $value)
    {
        $this->twig->addGlobal($name, $value);
    }

    /**
     * Rendering
     *
     * @param $template
     * @param $variables
     * @return $this
     */
    public function render($template, $variables)
    {
        $this->rendered = $this->twig->render($template, $variables);
        return $this;
    }

    /**
     * Save rendered file
     *
     * @param $pathname
     */
    public function save($pathname)
    {
        if (!is_dir($dir = dirname($pathname))) {
            $this->filesystem->mkdir($dir);
        }
        file_put_contents($pathname, $this->rendered);
    }

    public function debug()
    {
        echo $this->rendered . "\n";
    }
}