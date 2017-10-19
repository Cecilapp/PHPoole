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
use PHPoole\Collection\Collection;
use PHPoole\Collection\CollectionInterface;
use PHPoole\Collection\Page\Page;
use PHPoole\Exception\Exception;

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
            new \Twig_SimpleFilter('excerpt', [$this, 'excerpt']),
            new \Twig_SimpleFilter('excerptHtml', [$this, 'excerptHtml']),
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
            new \Twig_SimpleFunction('readtime', [$this, 'readtime']),
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

        if ($array instanceof Collection) {
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

        if ($array instanceof Collection) {
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

        /*
        if ($array instanceof CollectionInterface) {
            $array->usort($callback);
        } else {
            if (is_array($array)) {
                usort($array, $callback);
            }
        }
        */

        if ($array instanceof Collection) {
            $array = $array->toArray();
        }
        if (is_array($array)) {
            usort($array, $callback);
        }

        return $array;
    }

    /**
     * Create an URL.
     *
     * @param \Twig_Environment $env
     * @param null              $value
     * @param false             $canonical
     *
     * @return string
     */
    public function createUrl(\Twig_Environment $env, $value = null, $canonical = false)
    {
        $base = '';
        $baseurl = $env->getGlobals()['site']['baseurl'];

        if ($canonical || $env->getGlobals()['site']['canonicalurl'] !== false) {
            $base = rtrim($baseurl, '/');
        }

        if ($value instanceof Page) {
            $value = $value->getPermalink();
            if (false !== strpos($value, '.')) { // file URL (with a dot for extension)
                $url = $base.'/'.ltrim($value, '/');
            } else {
                $url = $base.'/'.ltrim(rtrim($value, '/').'/', '/');
            }
        } else {
            if (preg_match('~^(?:f|ht)tps?://~i', $value)) { // external URL
                $url = $value;
            } elseif (false !== strpos($value, '.')) { // file URL (with a dot for extension)
                $url = $base.'/'.ltrim($value, '/');
            } else {
                $value = $this->slugifyFilter($value);
                $url = $base.'/'.ltrim(rtrim($value, '/').'/', '/');
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

    /**
     * Read $lenght first characters of a string and add a suffix.
     *
     * @param $string
     * @param int    $length
     * @param string $suffix
     *
     * @return string
     */
    public function excerpt($string, $length = 450, $suffix = ' …')
    {
        $string = str_replace('</p>', '<br /><br />', $string);
        $string = trim(strip_tags($string, '<br>'), '<br />');
        if (mb_strlen($string) > $length) {
            $string = mb_substr($string, 0, $length);
            $string .= $suffix;
        }

        return $string;
    }

    /**
     * Read characters before '<!-- excerpt -->'.
     *
     * @param $string
     *
     * @return string
     */
    public function excerptHtml($string)
    {
        // https://regex101.com/r/mA2mG0/3
        $pattern = '^(.*)[\n\r\s]*<!-- excerpt -->[\n\r\s]*(.*)$';
        preg_match(
            '/'.$pattern.'/s',
            $string,
            $matches
        );
        if (!$matches) {
            return $string;
        }

        return trim($matches[1]);
    }

    /**
     * Calculate estimated time to read a text.
     *
     * @param $text
     *
     * @return float|string
     */
    public function readtime($text)
    {
        $words = str_word_count(strip_tags($text));
        $min = floor($words / 200);
        if ($min === 0) {
            return '1';
        }

        return $min;
    }
}
