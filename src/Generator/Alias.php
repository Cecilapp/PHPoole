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
 * Class Alias.
 */
class Alias implements GeneratorInterface
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

        /* @var $page Page */
        foreach ($pageCollection as $page) {
            $aliases = [];
            if ($page->hasVariable('aliases')) {
                $aliases = $page->getVariable('aliases');
            }
            if ($page->hasVariable('alias')) {
                $aliases[] = $page->getVariable('alias');
            }
            if (!empty($aliases)) {
                foreach ($aliases as $alias) {
                    /* @var $aliasPage Page */
                    $aliasPage = (new Page())
                        ->setId($alias.'/redirect')
                        ->setPathname(Page::urlize($alias))
                        ->setTitle($alias)
                        ->setLayout('redirect.html')
                        ->setVariable('destination', $page->getPermalink());
                    $generatedPages->add($aliasPage);
                }
            }
        }

        return $generatedPages;
    }
}
