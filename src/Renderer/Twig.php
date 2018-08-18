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
        $internalLoader = [];
        if ($internalLayouts = $config->get('layouts.internal')) {
            foreach ($internalLayouts as $layout => $path) {
                $layoutContent = file_get_contents(sprintf(__DIR__.'/../../res/layouts/%s.twig', $layout));
                $internalLoader[sprintf('%s%s.twig', (($path) ? $path : ''), $layout)] = $layoutContent;
            }
        }
        $loaderArray = new \Twig_Loader_Array($internalLoader);
        // project layouts
        $loaderFS = new \Twig_Loader_Filesystem($templatesPath);
        // load layouts
        $loader = new \Twig_Loader_Chain([$loaderFS, $loaderArray]);
        $this->twig = new \Twig_Environment($loader, [
            'autoescape'       => false,
            'strict_variables' => $this->twigStrict,
            'debug'            => $this->twigDebug,
            'cache'            => $this->twigCache,
        ]);
        // add extensions
        $this->twig->addExtension(new \Twig_Extension_Debug());
        $this->twig->addExtension(new TwigExtension($config->getOutputPath()));
        $this->twig->getExtension('Twig_Extension_Core')->setDateFormat($config->get('site.date.format'));
        $this->twig->getExtension('Twig_Extension_Core')->setTimezone($config->get('site.date.timezone'));

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

        // add generator meta
        if (!preg_match('/<meta name="generator".*/i', $this->rendered)) {
            $meta = '<meta name="generator" content="PHPoole" />';
            $this->rendered = preg_replace('/(<head>|<head[[:space:]]+.*>)/i', '$1'."\n\t".$meta, $this->rendered);
        }

        // replace excerpt tag by HTML anchor
        $pattern = '/(.*)(<!-- excerpt -->)(.*)/i';
        $replacement = '$1<span id="more"></span>$3';
        $this->rendered = preg_replace($pattern, $replacement, $this->rendered);

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
