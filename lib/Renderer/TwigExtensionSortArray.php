<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Renderer;

/**
 * Class TwigExtensionSortArray
 * @package PHPoole\Renderer
 */
class TwigExtensionSortArray extends \Twig_Extension
{
    /**
     * Returns a list of filters.
     *
     * @return array
     */
    public function getFilters()
    {
        $filters = array(
            new \Twig_SimpleFilter('sortmenubyweight', array($this, 'sortMenu')),
        );

        return $filters;
    }
    /**
     * Name of this extension
     *
     * @return string
     */
    public function getName()
    {
        return 'sortarray';
    }

    /**
     * Main method
     *
     * @param $array
     * @return mixed
     */
    function sortMenu($array)
    {
        usort($array, function($a, $b) {
            if (!array_key_exists('weight', $a) || !array_key_exists('weight', $b)) {
                return 0;
            }
            if ($a['weight'] == $b['weight']) {
                return 0;
            }
            return ($a['weight'] < $b['weight']) ? -1 : 1;
        });

        return $array;
    }
}