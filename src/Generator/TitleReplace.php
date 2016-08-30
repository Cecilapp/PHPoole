<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Generator;

use PHPoole\Page\Collection as PageCollection;
use PHPoole\Page\Page;

/**
 * Class TitleReplace.
 */
class TitleReplace implements GeneratorInterface
{
    /* @var \PHPoole\Config */
    protected $config;

    /**
     * {@inheritdoc}
     */
    public function __construct(\PHPoole\Config $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(PageCollection $pageCollection, \Closure $messageCallback)
    {
        $generatedPages = new PageCollection();

        $filteredPages = $pageCollection->filter(function (Page $page) {
            return false !== $page->getTitle();
        });

        /* @var $page Page */
        foreach ($filteredPages as $page) {
            $alteredPage = clone $page;
            $alteredPage->setTitle(strtoupper($page->getTitle()));
            $generatedPages->add($alteredPage);
        }

        return $generatedPages;
    }
}
