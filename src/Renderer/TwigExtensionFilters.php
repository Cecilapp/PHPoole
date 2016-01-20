<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Renderer;

/**
 * Class TwigExtensionFilters.
 */
class TwigExtensionFilters extends \Twig_Extension
{
    /**
     * Name of this extension.
     *
     * @return string
     */
    public function getName()
    {
        return 'filters';
    }

    /**
     * Returns a list of filters.
     *
     * @return \Twig_SimpleFilter[]
     */
    public function getFilters()
    {
        $filters = [
                new \Twig_SimpleFilter('filterBySection', [$this, 'filterBySection']),
        ];

        return $filters;
    }

    /**
     * Filter by section.
     *
     * @param \PHPoole\Page\Collection|array $pages
     * @param string                         $section
     *
     * @return array
     */
    public function filterBySection($pages, $section)
    {
        $filtered = [];

        foreach ($pages as $page) {
            if ($page instanceof \PHPoole\Page\Page) {
                if ($page->getSection() == $section) {
                    $filtered[] = $page;
                }
            }
        }

        return $filtered;
    }
}
