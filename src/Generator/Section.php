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
 * Class Section.
 */
class Section implements GeneratorInterface
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
            foreach ($sections as $section => $pages) {
                if (!$pageCollection->has($section.'/index')) {
                    usort($pages, 'PHPoole\Page\Utils::sortByDate');
                    $page = (new Page())
                        ->setId(Page::urlize(sprintf('%s/index', $section)))
                        ->setPathname(Page::urlize(sprintf('%s', $section)))
                        ->setTitle(ucfirst($section))
                        ->setNodeType(NodeType::SECTION)
                        ->setVariable('pages', $pages)
                        ->setVariable('menu', [
                            'main' => ['weight' => $menuWeight],
                        ]);
                    $generatedPages->add($page);
                }
                $menuWeight += 10;
            }
        }

        return $generatedPages;
    }
}
