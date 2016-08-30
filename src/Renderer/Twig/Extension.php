<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Renderer\Twig;

use Cocur\Slugify\Bridge\Twig\SlugifyExtension;
use Cocur\Slugify\Slugify;
use MatthiasMullie\Minify;
use PHPoole\Collection\AbstractCollection;
use PHPoole\Collection\CollectionInterface;
use PHPoole\Exception\Exception;
use PHPoole\Page\Page;

/**
 * Class Twig\Extension.
 */
class Extension extends SlugifyExtension
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
        parent::__construct(Slugify::create([
            'regexp' => Page::SLUGIFY_PATTERN,
        ]));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'phpoole';
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('filterBySection', [$this, 'filterBySection']),
            new \Twig_SimpleFilter('filterBy', [$this, 'filterBy']),
            new \Twig_SimpleFilter('sortByTitle', [$this, 'sortByTitle']),
            new \Twig_SimpleFilter('sortByWeight', [$this, 'sortByWeight']),
            new \Twig_SimpleFilter('sortByDate', [$this, 'sortByDate']),
            new \Twig_SimpleFilter('urlize', [$this, 'slugifyFilter']),
            new \Twig_SimpleFilter('minifyCSS', [$this, 'minifyCss']),
            new \Twig_SimpleFilter('minifyJS', [$this, 'minifyJs']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('url', [$this, 'createUrl'], ['needs_environment' => true]),
            new \Twig_SimpleFunction('minify', [$this, 'minify']),
        ];
    }

    /**
     * Filter by section.
     *
     * @param \PHPoole\Page\Collection $pages
     * @param string                   $section
     *
     * @return array
     */
    public function filterBySection($pages, $section)
    {
        return $this->filterBy($pages, 'section', $section);
    }

    /**
     * Filter by variable.
     *
     * @param \PHPoole\Page\Collection $pages
     * @param string                   $variable
     * @param string                   $value
     *
     * @throws Exception
     *
     * @return array
     */
    public function filterBy($pages, $variable, $value)
    {
        $filtered = [];

        foreach ($pages as $page) {
            if ($page instanceof Page) {
                $method = 'get'.ucfirst($variable);
                if (method_exists($page, $method)) {
                    if ($page->$method() == $value) {
                        $filtered[] = $page;
                    }
                } else {
                    if ($page->getVariable($variable) == $value) {
                        $filtered[] = $page;
                    }
                }
            } else {
                throw new Exception("'filterBy' available for page only!");
            }
        }

        return $filtered;
    }

    /**
     * Sort by title.
     *
     * @param $array|CollectionInterface
     *
     * @return mixed
     */
    public function sortByTitle($array)
    {
        $callback = function ($a, $b) {
            if (!isset($a['title'])) {
                return 1;
            }
            if (!isset($b['title'])) {
                return -1;
            }
            if ($a['title'] == $b['title']) {
                return 0;
            }

            return ($a['title'] > $b['title']) ? -1 : 1;
        };

        if ($array instanceof AbstractCollection) {
            $array = $array->toArray();
        }
        if (is_array($array)) {
            usort($array, $callback);
        }

        return $array;
    }

    /**
     * Sort by weight.
     *
     * @param $array|CollectionInterface
     *
     * @return mixed
     */
    public function sortByWeight($array)
    {
        $callback = function ($a, $b) {
            if (!isset($a['weight'])) {
                return 1;
            }
            if (!isset($b['weight'])) {
                return -1;
            }
            if ($a['weight'] == $b['weight']) {
                return 0;
            }

            return ($a['weight'] < $b['weight']) ? -1 : 1;
        };

        if ($array instanceof AbstractCollection) {
            $array = $array->toArray();
        }
        if (is_array($array)) {
            usort($array, $callback);
        }

        return $array;
    }

    /**
     * Sort by date.
     *
     * @param $array|CollectionInterface
     *
     * @return mixed
     */
    public function sortByDate($array)
    {
        $callback = function ($a, $b) {
            if (!isset($a['date'])) {
                return -1;
            }
            if (!isset($b['date'])) {
                return 1;
            }
            if ($a['date'] == $b['date']) {
                return 0;
            }

            return ($a['date'] > $b['date']) ? -1 : 1;
        };

        if ($array instanceof CollectionInterface) {
            $array->usort($callback);
        } else {
            if (is_array($array)) {
                usort($array, $callback);
            }
        }

        return $array;
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
            } elseif (false !== strpos($value, '.')) {
                $url = rtrim($baseurl, '/').'/'.ltrim($value, '/');
            } else {
                $value = $this->slugifyFilter($value);
                $url = rtrim($baseurl, '/').'/'.ltrim($value, '/');
            }
        }

        return $url;
    }

    /**
     * Minify a CSS or a JS file.
     *
     * @param string $path
     *
     * @throws Exception
     *
     * @return string
     */
    public function minify($path)
    {
        $filePath = $this->destPath.'/'.$path;
        if (is_file($filePath)) {
            $extension = (new \SplFileInfo($filePath))->getExtension();
            switch ($extension) {
                case 'css':
                    $minifier = new Minify\CSS($filePath);
                    break;
                case 'js':
                    $minifier = new Minify\JS($filePath);
                    break;
                default:
                    throw new Exception(sprintf("File '%s' should be a '.css' or a '.js'!", $path));
            }
            $minifier->minify($filePath);

            return $path;
        }
        throw new Exception(sprintf("File '%s' doesn't exist!", $path));
    }

    /**
     * Minify CSS.
     *
     * @param $value
     *
     * @return string
     */
    public function minifyCss($value)
    {
        $minifier = new Minify\CSS($value);

        return $minifier->minify();
    }

    /**
     * Minify JS.
     *
     * @param $value
     *
     * @return string
     */
    public function minifyJs($value)
    {
        $minifier = new Minify\JS($value);

        return $minifier->minify();
    }
}
