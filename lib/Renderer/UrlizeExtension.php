<?php

namespace PHPoole\Renderer;

use Cocur\Slugify\Bridge\Twig\SlugifyExtension;

class UrlizeExtension extends SlugifyExtension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('urlize', array($this, 'slugifyFilter')),
        );
    }
}