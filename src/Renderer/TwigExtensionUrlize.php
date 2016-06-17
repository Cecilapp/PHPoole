<?php

namespace PHPoole\Renderer;

use Cocur\Slugify\Bridge\Twig\SlugifyExtension;
use Cocur\Slugify\Slugify;
use PHPoole\Page\Page;

/**
 * Class TwigExtensionUrlize.
 */
class TwigExtensionUrlize extends SlugifyExtension
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(Slugify::create([
            'regexp' => Page::SLUGIFY_PATTERN]
        ));
    }

    /**
     * Name of this extension.
     *
     * @return string
     */
    public function getName()
    {
        return 'urlize_extension';
    }

    /**
     * Returns a list of filters.
     *
     * @return \Twig_SimpleFilter[]
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('urlize', [$this, 'slugifyFilter']),
        ];
    }

    /**
     * Returns a list of functions.
     *
     * @return \Twig_SimpleFunction[]
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('url', [$this, 'createUrl'], ['needs_environment' => true]),
        ];
    }

    /**
     * Create an URL.
     *
     * @param \Twig_Environment $env
     * @param null              $value
     *
     * @return string
     */
    public function createUrl(\Twig_Environment $env, $value = null)
    {
        $baseurl = $env->getGlobals()['site']['baseurl'];

        if ($value instanceof Page) {
            $value = $value->getPermalink();
            $url = rtrim($baseurl, '/').'/'.ltrim($value, '/');
        } else {
            if (preg_match('~^(?:f|ht)tps?://~i', $value)) {
                $url = $value;
            } else {
                $value = $this->slugifyFilter($value);
                $url = rtrim($baseurl, '/').'/'.ltrim($value, '/');
            }
        }

        return $url;
    }
}
