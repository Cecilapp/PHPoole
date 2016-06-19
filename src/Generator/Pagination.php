<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Generator;

use Dflydev\DotAccessData\Data;
use PHPoole\Page\Collection as PageCollection;
use PHPoole\Page\NodeTypeEnum;
use PHPoole\Page\Page;

/**
 * Class Pagination.
 */
class Pagination implements GeneratorInterface
{
    /* @var Data $options */
    protected $options;

    protected $paginate;

    protected $disabled;

    protected $paginateMax;

    protected $paginatePath;

    /**
     * Pagination constructor.
     *
     * @param Data $options
     */
    public function __construct(Data $options)
    {
        $this->options = $options;

        $this->paginate = $this->options->get('site.paginate');
        $this->disabled = ($this->paginate == 'disabled') ? true : false;
        $this->paginateMax = $this->paginate['max'];
        $this->paginatePath = $this->paginate['path'];
    }

    /**
     * {@inheritdoc}
     */
    public function generate(PageCollection $pageCollection, \Closure $messageCallback)
    {
        $generatedPages = new PageCollection();

        if ($this->disabled) {
            return $generatedPages;
        }

        $filteredPages = $pageCollection->filter(function (Page $page) {
            return $page->getNodeType() !== null;
        });
        $pages = $filteredPages->sortByDate()->toArray();

        /* @var $page Page */
        $page = (new Page())
            ->setId('index')
            ->setNodeType(NodeTypeEnum::HOMEPAGE)
            ->setPathname(Page::urlize(''))
            ->setTitle('Home')
            ->setVariable('pages', $pages)
            ->setVariable('menu', [
                'main' => ['weight' => 1],
            ]);
        $generatedPages->add($page);

        return $generatedPages;
    }
}

/*
protected function addNodePage(
        $type,
        $title,
        $path,
        array $pages,
        array $variables = [],
        $menuWeight = 0
    ) {
        $paginate = $this->getOption('site.paginate');
        $disabled = ($paginate == 'disabled') ? true : false;
        $paginateMax = $paginate['max'];
        $paginatePath = $paginate['path'];
        // paginate
        if (!$disabled && (isset($paginateMax) && count($pages) > $paginateMax)) {
            $paginateCount = ceil(count($pages) / $paginateMax);
            for ($i = 0; $i < $paginateCount; $i++) {
                $pagesInPagination = array_slice($pages, ($i * $paginateMax), ($i * $paginateMax) + $paginateMax);
                // first
                if ($i == 0) {
                    $page = (new Page())
                        ->setId(Page::urlize(sprintf('%s/index', $path)))
                        ->setPathname(Page::urlize(sprintf('%s', $path)))
                        ->setVariable('aliases', [
                            sprintf('%s/%s/%s', $path, $paginatePath, 1),
                        ]);
                    if ($menuWeight) {
                        $page->setVariable('menu', [
                            'main' => ['weight' => $menuWeight],
                        ]);
                    }
                // others
                } else {
                    $page = (new Page())
                        ->setId(Page::urlize(sprintf('%s/%s/%s/index', $path, $paginatePath, $i + 1)))
                        ->setPathname(Page::urlize(sprintf('%s/%s/%s', $path, $paginatePath, $i + 1)));
                }
                // pagination
                $pagination = ['pages' => $pagesInPagination];
                if ($i > 0) {
                    $pagination += ['prev' => Page::urlize(sprintf('%s/%s/%s', $path, $paginatePath, $i))];
                }
                if ($i < $paginateCount - 1) {
                    $pagination += ['next' => Page::urlize(sprintf('%s/%s/%s', $path, $paginatePath, $i + 2))];
                }
                // common properties/variables
                $page->setTitle(ucfirst($title))
                    ->setNodeType($type)
                    ->setVariable('pages', $pages)
                    ->setVariable('pagination', $pagination);
                if (!empty($variables)) {
                    foreach ($variables as $key => $value) {
                        $page->setVariable($key, $value);
                    }
                }
                $this->pageCollection->add($page);
            }
        // not paginate
        } else {
            $page = (new Page())
                ->setId(Page::urlize(sprintf('%s/index', $path)))
                ->setPathname(Page::urlize(sprintf('%s', $path)))
                ->setTitle(ucfirst($title))
                ->setNodeType($type)
                ->setVariable('pages', $pages)
                ->setVariable('pagination', ['pages' => $pages]);
            if ($menuWeight) {
                $page->setVariable('menu', [
                    'main' => ['weight' => $menuWeight],
                ]);
            }
            if (!empty($variables)) {
                foreach ($variables as $key => $value) {
                    $page->setVariable($key, $value);
                }
            }
            $this->pageCollection->add($page);
        }
    }
*/
