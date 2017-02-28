<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Renderer;

use PHPoole\Renderer\Twig\Extension as TwigExtension;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class Twig.
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
    protected $twigStrict = true;
    /**
     * @var bool
     */
    protected $twigDebug = true;
    /*
     * @var string|bool
     */
    protected $twigCache = false;

    /**
     * {@inheritdoc}
     */
    public function __construct($templatesPath, $config)
    {
        // internal layouts
        $loaderArray = new \Twig_Loader_Array([
            'redirect.html.twig' => file_get_contents(__DIR__.'/../../res/layouts/redirect.html.twig'),
        ]);
        // project layouts
        $loaderFS = new \Twig_Loader_Filesystem($templatesPath);
        // load layouts
        $loader = new \Twig_Loader_Chain([$loaderFS, $loaderArray]);
        $this->twig = new \Twig_Environment($loader,
            [
                'autoescape'       => false,
                'strict_variables' => $this->twigStrict,
                'debug'            => $this->twigDebug,
                'cache'            => $this->twigCache,
            ]
        );
        // add extensions
        $this->twig->addExtension(new \Twig_Extension_Debug());
        $this->twig->addExtension(new TwigExtension($config->getOutputPath()));
        $this->twig->getExtension('core')->setDateFormat($config->get('site.date.format'));
        $this->twig->getExtension('core')->setTimezone($config->get('site.date.timezone'));

        $this->fs = new Filesystem();
    }

    /**
     * {@inheritdoc}
     */
    public function addGlobal($name, $value)
    {
        $this->twig->addGlobal($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function render($template, $variables)
    {
        $this->rendered = $this->twig->render($template, $variables);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function save($pathname)
    {
        if (!is_dir($dir = dirname($pathname))) {
            $this->fs->mkdir($dir);
        }

        return false !== @file_put_contents($pathname, $this->rendered);
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($template)
    {
        try {
            $this->twig->parse($this->twig->tokenize($template));

            return true;
        } catch (\Twig_Error_Syntax $e) {
            return false;
        }
    }
}
