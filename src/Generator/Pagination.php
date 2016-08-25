<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Generator;

use PHPoole\Options;
use PHPoole\Page\Collection as PageCollection;
use PHPoole\Page\NodeType;
use PHPoole\Page\Page;

/**
 * Class Pagination.
 */
class Pagination implements GeneratorInterface
{
    /* @var Options $options */
    protected $options;

    /**
     * Pagination constructor.
     *
     * @param Options $options
     */
    public function __construct(Options $options)
    {
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(PageCollection $pageCollection, \Closure $messageCallback)
    {
        $generatedPages = new PageCollection();

        $filteredPages = $pageCollection->filter(function (Page $page) {
            return in_array($page->getNodeType(), [NodeType::HOMEPAGE, NodeType::SECTION]);
        });

        /* @var $page Page */
        foreach ($filteredPages as $page) {
            $paginate = $this->options->get('site.paginate');

            $disabled = array_key_exists('disabled', $paginate) && $paginate['disabled'];
            if ($disabled) {
                return $generatedPages;
            }

            $paginateMax = $paginate['max'];
            $paginatePath = $paginate['path'];
            $pages = $page->getVariable('pages');
            $path = $page->getPathname();

            // paginate
            if (!$disabled && (isset($paginateMax) && count($pages) > $paginateMax)) {
                $paginateCount = ceil(count($pages) / $paginateMax);
                for ($i = 0; $i < $paginateCount; $i++) {
                    $pagesInPagination = array_slice($pages, ($i * $paginateMax), ($i * $paginateMax) + $paginateMax);
                    $alteredPage = clone $page;
                    // first page
                    if ($i == 0) {
                        $alteredPage
                            ->setId(Page::urlize(sprintf('%s/index', $path)))
                            ->setPathname(Page::urlize(sprintf('%s', $path)))
                            ->setVariable('aliases', [
                                sprintf('%s/%s/%s', $path, $paginatePath, 1),
                            ]);
                    } else {
                        $alteredPage
                            ->setId(Page::urlize(sprintf('%s/%s/%s/index', $path, $paginatePath, $i + 1)))
                            ->setPathname(Page::urlize(sprintf('%s/%s/%s', $path, $paginatePath, $i + 1)))
                            ->unVariable('menu');
                    }
                    // pagination
                    $pagination = ['pages' => $pagesInPagination];
                    if ($i > 0) {
                        $pagination += ['prev' => Page::urlize(sprintf('%s/%s/%s', $path, $paginatePath, $i))];
                    }
                    if ($i < $paginateCount - 1) {
                        $pagination += ['next' => Page::urlize(sprintf('%s/%s/%s', $path, $paginatePath, $i + 2))];
                    }
                    $alteredPage->setVariable('pagination', $pagination);

                    $generatedPages->add($alteredPage);
                }
            }
        }

        return $generatedPages;
    }
}
