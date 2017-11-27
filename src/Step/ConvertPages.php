<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Step;

use PHPoole\Collection\Page\Page;
use PHPoole\Converter\Converter;

/**
 * Converts content of all pages.
 */
class ConvertPages extends AbstractStep
{
    /**
     * {@inheritdoc}
     */
    public function internalProcess()
    {
        if (count($this->phpoole->getPages()) <= 0) {
            return;
        }
        $max = count($this->phpoole->getPages());
        $count = 0;
        $error = 0;
        $message = '';
        /* @var $page Page */
        foreach ($this->phpoole->getPages() as $page) {
            if (!$page->isVirtual()) {
                $count++;

                try {
                    $convertedPage = $this->convertPage($page, $this->phpoole->getConfig()->get('frontmatter.format'));
                    $message = $page->getName();
                    // force convert drafts?
                    if ($this->phpoole->getConfig()->get('drafts')) {
                        $page->setVariable('published', true);
                    }
                    if ($page->getVariable('published')) {
                        $this->phpoole->getPages()->replace($page->getId(), $convertedPage);
                    } else {
                        $this->phpoole->getPages()->remove($page->getId());
                        $message .= ' (not published)';
                    }
                    call_user_func_array($this->phpoole->getMessageCb(), ['CONVERT', 'TYPE', $message, $count, $max]);
                } catch (\Exception $e) {
                    $this->phpoole->getPages()->remove($page->getId());
                    $error++;
                    $message = $e->getMessage();
                }
            }
        }
        if ($error > 0) {
            $message = '[ERROR] '.$message;
            call_user_func_array($this->phpoole->getMessageCb(), ['CONVERT', 'ERROR', $message, $count - $error, $max]);
        }
    }

    /**
     * Converts page content:
     * - Yaml frontmatter to PHP array
     * - Markdown body to HTML.
     *
     * @param Page   $page
     * @param string $format
     *
     * @return Page
     */
    public function convertPage(Page $page, $format = 'yaml')
    {
        // converts frontmatter
        try {
            $variables = Converter::convertFrontmatter($page->getFrontmatter(), $format);
        } catch (\Exception $e) {
            $message = sprintf("Unable to convert frontmatter of '%s': %s", $page->getId(), $e->getMessage());

            throw new \Exception($message);
        }
        // set variables
        try {
            $page->setVariables($variables);
        } catch (\Exception $e) {
            $message = sprintf("Unable to set variable in '%s': %s", $page->getId(), $e->getMessage());

            throw new \Exception($message);
        }

        // converts body
        $html = Converter::convertBody($page->getBody());
        $page->setHtml($html);

        return $page;
    }
}
