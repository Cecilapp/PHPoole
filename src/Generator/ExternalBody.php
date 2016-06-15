<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Generator;

use PHPoole\Converter\Converter;
use PHPoole\Page\Collection as PageCollection;
use PHPoole\Page\Page;

/**
 * Class Homepage.
 */
class ExternalBody implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(PageCollection $pageCollection)
    {
        $generatedPages = new PageCollection();

        $filteredPages = $pageCollection->filter(function (Page $page) {
            return $page->getVariable('external') != null;
        });

        /* @var $page Page */
        foreach ($filteredPages as $page) {
            if (false === $pageContent = @file_get_contents($page->getVariable('external'), false)) {
                throw new \Exception(sprintf("Cannot get contents from %s\n", $page->getVariable('external')));
            }
            $html = (new Converter())
                ->convertBody($pageContent);
            $page->setHtml($html);

            $generatedPages->add($page);
        }

        return $generatedPages;
    }
}
