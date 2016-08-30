<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Step;

use PHPoole\Converter\Converter;
use PHPoole\Exception\Exception;
use PHPoole\Generator\GeneratorManager;
use PHPoole\Page\Page;
use PHPoole\PHPoole;

/**
 * Generates virtual pages.
 */
class GeneratePages implements StepInterface
{
    protected $phpoole;
    protected $process = false;

    public function __construct(PHPoole $PHPoole)
    {
        $this->phpoole = $PHPoole;
    }

    public function init()
    {
        if (count($this->phpoole->getConfig()->get('generators')) > 0) {
            $this->process = true;
        }
    }

    public function process()
    {
        if ($this->process) {
            $generatorManager = new GeneratorManager();
            $generators = $this->phpoole->getConfig()->get('generators');
            array_walk($generators, function ($generator, $priority) use ($generatorManager) {
                if (!class_exists($generator)) {
                    $message = sprintf("> Unable to load generator '%s'", $generator);
                    call_user_func_array($this->phpoole->getMessageCb(), ['GENERATE_PROGRESS', $message]);

                    return;
                }
                $generatorManager->addGenerator(new $generator($this->phpoole->getConfig()), $priority);
            });
            call_user_func_array($this->phpoole->getMessageCb(), ['GENERATE', 'Generating pages']);
            $pages = $generatorManager->process($this->phpoole->getPages(), $this->phpoole->getMessageCb());
            $this->phpoole->setPages($pages);
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
        } catch (Exception $e) {
            $message = sprintf("> Unable to convert frontmatter of '%s': %s", $page->getId(), $e->getMessage());
            call_user_func_array($this->phpoole->getMessageCb(), ['CONVERT_PROGRESS', $message]);

            return false;
        }
        $page->setVariables($variables);

        // converts body
        $html = Converter::convertBody($page->getBody());
        $page->setHtml($html);

        return $page;
    }
}
