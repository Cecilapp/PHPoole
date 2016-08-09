<?php

namespace PHPoole\Renderer;

use MatthiasMullie\Minify;

/**
 * Class TwigExtensionMinify.
 */
class TwigExtensionMinify extends \Twig_Extension
{
    /* @var string */
    protected $destPath;

    /**
     * Constructor.
     *
     * @param string $destPath
     */
    public function __construct($destPath)
    {
        $this->destPath = $destPath;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'minify';
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('minify', [$this, 'minify']),
        ];
    }

    /**
     * Minify CSS.
     *
     * @param string $path
     *
     * @throws \Exception
     *
     * @return string
     */
    public function minify($path)
    {
        $filePath = $this->destPath.'/'.$path;
        if (is_file($filePath)) {
            $minifier = new Minify\CSS($filePath);
            $minifier->minify($filePath);

            return $path;
        }
        throw new \Exception(sprintf("File '%s' doesn't exist!", $filePath));
    }
}
