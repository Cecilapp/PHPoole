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
    protected $templatesDir;
    /**
     * @var string
     */
    protected $rendered;
    /**
     * @var Filesystem
     */
    protected $fs;
    /**
     * @var bool
     */
    protected $twigStrict = false;
    /**
     * @var bool
     */
    protected $twigDebug = true;

    /**
     * {@inheritdoc}
     */
    public function __construct($templatesPath = '')
    {
        if (!empty($templatesPath)) {
            $this->templatesDir = $templatesPath;
        }

        $loaderFS    = new \Twig_Loader_Filesystem($this->templatesDir);
        $loaderArray = new \Twig_Loader_Array(array(
            'redirect.html' => '<!DOCTYPE html>
<html>
<head lang="en">
    <link rel="canonical" href="{{ url(page.destination) }}"/>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta http-equiv="refresh" content="0;url={{ url(page.destination) }}" />
</head>
</html>',
        ));
        $loader = new \Twig_Loader_Chain(array($loaderFS, $loaderArray));
        $this->twig = new \Twig_Environment($loader,
            [
                'autoescape'       => false,
                'strict_variables' => $this->twigStrict,
                'debug'            => $this->twigDebug
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

        $this->fs = new Filesystem();
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function addGlobal($name, $value)
    {
        $this->twig->addGlobal($name, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function render($template, $variables)
    {
        $this->rendered = $this->twig->render($template, $variables);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function save($pathname)
    {
        if (!is_dir($dir = dirname($pathname))) {
            $this->fs->mkdir($dir);
        }
        if (false!== @file_put_contents($pathname, $this->rendered)) {
            return true;
        }
    }
}