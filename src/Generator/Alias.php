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
    /**
     * @var array
     */
    private static $pages = [];

    /**
     * {@inheritdoc}
     */
    public static function generate(PageCollection $pageCollection)
    {
        /* @var $page Page */
        foreach ($pageCollection as $page) {
            if ($page->hasVariable('aliases')) {
                $aliases = $page->getVariable('aliases');
                foreach ($aliases as $alias) {
                    /* @var $redirectPage Page */
                    $aliasPage = new Page();
                    $aliasPage->setId($alias)
                        ->setPathname(Page::urlize($alias))
                        ->setTitle($alias)
                        ->setLayout('redirect')
                        ->setVariable('destination', $page->getPermalink());
                    self::$pages[] = $aliasPage;
                }
            }
        }

        return self::$pages;
    }
}
