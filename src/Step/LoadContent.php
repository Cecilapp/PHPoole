<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Step;

use PHPoole\Exception\Exception;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Loads files in the 'content' directory.
 */
class LoadContent extends AbstractStep
{
    // pattern of a file with a front matter
    // https://regex101.com/r/xH7cL3/1
    const PATTERN = '^\s*(?:<!--|---|\+++){1}[\n\r\s]*(.*?)[\n\r\s]*(?:-->|---|\+++){1}[\s\n\r]*(.*)$';
    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function init()
    {
        if (!is_dir($this->phpoole->getConfig()->getContentPath())) {
            throw new Exception(sprintf('%s not found!', $this->phpoole->getConfig()->getContentPath()));
        }
        $this->process = true;
    }

    /**
     * {@inheritdoc}
     */
    public function internalProcess()
    {
        $content = [];
        // collects files in each supported format
        foreach ($this->phpoole->getConfig()->get('content.format') as $format => $data) {
            $files = Finder::create()
                ->files()
                ->in($this->phpoole->getConfig()->getContentPath())
                ->name('/\.('.implode('|', $data['ext']).')$/');
            /* @var $file SplFileInfo */
            foreach ($files as $file) {
                $index = $file->getRelativePathname();
                $properties = $this->parse(
                    $file->getContents(),
                    $this->phpoole->getConfig()->get('content.frontmatter.'.$data['frontmatter'].'.parser')
                );
                $content[$index] = $properties;
                $content[$index]['content'] = '';
                $content[$index]['format'] = $format;
                $content[$index]['lastmodified'] = $file->getMTime();
            }
        }

        var_dump($content);

        $this->phpoole->setContent($content);
    }

    /**
     * Parse the contents of a file.
     *
     * Example:
     * ```
     *     ---
     *     title: Title
     *     date: 2016-07-29
     *     ---
     *     Lorem Ipsum.
     * ```
     *
     * @param string $content
     *
     * @param string $fmParser Class name
     *
     * @return array
     */
    public function parse($content, $fmParser)
    {
        $properties = [];

        // parse front matter
        preg_match(
            '/'.self::PATTERN.'/s',
            $content,
            $matches
        );
        // if not front matter, set 'content' property only
        if (!$matches) {
            $properties['raw'] = $content;

            return $properties;
        }
        // parse front matter
        /* @var $parser \PHPoole\Parser\ParserInterface */
        $parser = new $fmParser();
        $properties = $parser->parse(trim($matches[1]));
        $properties['raw'] = trim($matches[2]);

        return $properties;
    }
}
