<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Generator;

use PHPoole\Page\Collection as PageCollection;
use PHPoole\Page\NodeTypeEnum;
use PHPoole\Page\Page;

/**
 * Class Section.
 */
class Section implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(PageCollection $pageCollection)
    {
        $sections = [];

        // collects sections
        /* @var $page Page */
        foreach ($pageCollection as $page) {
            if ($page->getSection() != '') {
                $sections[$page->getSection()][] = $page;
            }
        }
        // adds node pages to collection
        if (count($sections) > 0) {
            $menuWeight = 100;
            foreach ($sections as $node => $pages) {
                if (!$pageCollection->has($node)) {
                    usort($pages, 'PHPoole\Page\Utils::sortByDate');
                    $page = (new Page())
                        ->setId(Page::urlize(sprintf('%s/index', $node)))
                        ->setPathname(Page::urlize(sprintf('%s', $node)))
                        ->setTitle(ucfirst($node))
                        ->setNodeType(NodeTypeEnum::SECTION)
                        ->setVariable('pages', $pages);
                        $page->setVariable('menu', [
                            'main' => ['weight' => $menuWeight],
                        ]);
                    $pageCollection->add($page);
                }
                $menuWeight += 10;
            }
        }

        return $pageCollection;
    }
}
