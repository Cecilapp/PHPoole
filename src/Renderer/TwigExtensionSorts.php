<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Renderer;

/**
 * Class TwigExtensionSorts.
 */
class TwigExtensionSorts extends \Twig_Extension
{
    /**
     * Name of this extension.
     *
     * @return string
     */
    public function getName()
    {
        return 'sorts';
    }

    /**
     * Returns a list of filters.
     *
     * @return \Twig_SimpleFilter[]
     */
    public function getFilters()
    {
        $filters = [
            new \Twig_SimpleFilter('sortByTitle', [$this, 'sortByTitle']),
            new \Twig_SimpleFilter('sortByWeight', [$this, 'sortByWeight']),
            new \Twig_SimpleFilter('sortByDate', [$this, 'sortByDate']),
        ];

        return $filters;
    }

    /**
     * Sort by title.
     *
     * @param $array|\PHPoole\Collection\CollectionInterface
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

        if ($array instanceof \PHPoole\Collection\AbstractCollection) {
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
     * @param $array|\PHPoole\Collection\CollectionInterface
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

        if ($array instanceof \PHPoole\Collection\AbstractCollection) {
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
     * @param $array|\PHPoole\Collection\CollectionInterface
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

        if ($array instanceof \PHPoole\Collection\CollectionInterface) {
            $array->usort($callback);
        } else {
            if (is_array($array)) {
                usort($array, $callback);
            }
        }

        return $array;
    }
}
