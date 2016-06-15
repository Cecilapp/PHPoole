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
    public function generate(PageCollection $pageCollection, \Closure $messageCallback)
    {
        $generatedPages = new PageCollection();

        $filteredPages = $pageCollection->filter(function (Page $page) {
            return $page->getVariable('external') != null;
        });

        /* @var $page Page */
        foreach ($filteredPages as $page) {
            try {
                $pageContent = file_get_contents($page->getVariable('external'), false);
                $html = (new Converter())
                    ->convertBody($pageContent);
                $page->setHtml($html);

                $generatedPages->add($page);
            } catch (\Exception $e) {
                $error = sprintf("Cannot get contents from %s", $page->getVariable('external'));
                $message = sprintf("> Unable to generate '%s': %s", $page->getId(), $error);
                call_user_func_array($messageCallback, ['GENERATE_PROGRESS', $message]);
            }

        }

        return $generatedPages;
    }
}
