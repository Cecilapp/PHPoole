<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Generator;

use PHPoole\Page\Collection as PageCollection;
use PHPoole\Page\NodeType;
use PHPoole\Page\Page;

/**
 * Class Homepage.
 */
class Homepage implements GeneratorInterface
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

        if (!$pageCollection->has('index')) {
            $filteredPages = $pageCollection->filter(function (Page $page) {
                return $page->getNodeType() === null
                && $page->getSection() == $this->config->get('site.paginate.homepage.section')
                && !empty($page->getBody());
            });
            $pages = $filteredPages->sortByDate()->toArray();

            /* @var $page Page */
            $page = (new Page())
                ->setId('index')
                ->setNodeType(NodeType::HOMEPAGE)
                ->setPathname(Page::urlize(''))
                ->setTitle('Home')
                ->setVariable('pages', $pages)
                ->setVariable('menu', [
                    'main' => ['weight' => 1],
                ]);
            $generatedPages->add($page);
        }

        return $generatedPages;
    }
}
