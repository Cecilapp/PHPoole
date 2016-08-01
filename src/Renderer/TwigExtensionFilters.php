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
            new \Twig_SimpleFilter('filterBy', [$this, 'filterBy']),
        ];

        return $filters;
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
     * @return array
     */
    public function filterBy($pages, $variable, $value)
    {
        $filtered = [];

        foreach ($pages as $page) {
            if ($page instanceof \PHPoole\Page\Page) {
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
                throw new \Exception("'filterBy' available for page only!");
            }
        }

        return $filtered;
    }
}
