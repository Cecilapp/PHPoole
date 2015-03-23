<?php

namespace PHPoole\Renderer;

use Cocur\Slugify\Bridge\Twig\SlugifyExtension;
use Cocur\Slugify\Slugify;
use PHPoole\Page\Page;

class TwigExtensionUrlize extends SlugifyExtension
{
    public function __construct()
    {
        parent::__construct(Slugify::create(Page::SLUGIFY_PATTERN));
    }

    public function getName()
    {
        return 'urlize_extension';
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('urlize', array($this, 'slugifyFilter')),
        );
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('url', array($this, 'createUrl'), array('needs_environment' => true)),
        );
    }

    public function createUrl(\Twig_Environment $env, $value = null)
    {
        if ($value instanceof Page) {
            $value = $value->getPathname();
        } else {
            $value = $this->slugifyFilter($value);
        }

        $baseurl = $env->getGlobals()['site']['baseurl'];
        $url =  rtrim($baseurl, "/") . '/' . $value;

        return $url;
    }
}