<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Step;

use PHPoole\Exception\Exception;
use PHPoole\Util;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Loads files in the 'content' directory.
 */
class LoadContent extends AbstractStep
{
    // pattern of a file with a front matter
    // https://regex101.com/r/xH7cL3/1
    const FM_PATTERN = '^\s*(?:<!--|---|\+++){1}[\n\r\s]*(.*?)[\n\r\s]*(?:-->|---|\+++){1}[\s\n\r]*(.*)$';

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
        $properties = [];
        // collects files in each supported format
        foreach ($this->phpoole->getConfig()->get('content.format') as $format => $data) {
            $files = Finder::create()
                ->files()
                ->in($this->phpoole->getConfig()->getContentPath())
                ->name('/\.('.implode('|', $data['ext']).')$/');
            /* @var $file SplFileInfo */
            foreach ($files as $file) {
                $index = $file->getRelativePathname();
<<<<<<< Updated upstream
                if (array_key_exists('frontmatter', $data)) {
                    $fmProperties = $this->parse(
                        $file->getContents(),
                        $this->phpoole->getConfig()->get('content.frontmatter.'.$data['frontmatter'].'.parser')
                    );
                    $properties[$index] = $fmProperties;
                }
                $properties[$index]['id'] = $index;
                $properties[$index]['format'] = $format;
                $properties[$index]['lastmodified'] = $file->getMTime();
=======

                $content[$index]['id']       = $index;
                $content[$index]['filepath'] = $index;
                $content[$index]['path']     = Util::urlize(trim($index, '.'.$format));

                $content[$index]['format']   = $format;
                $content[$index]['lastmodified'] = $file->getMTime();

                // front matter?
                if (isset($data['frontmatter'])) {
                    $content[$index] = array_merge($content[$index], $this->parseFM(
                        $file->getContents(),
                        $this->phpoole->getConfig()->get('content.frontmatter.'.$data['frontmatter'].'.parser')
                    )) ;
                }
>>>>>>> Stashed changes
            }
        }

        // DEBUG
        //echo 'content:'.PHP_EOL;
        //print_r($content);

        $this->phpoole->setContent($properties);
    }

    /**
     * Parse front matter of a file.
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
     * @param string $fmParser Class name
     *
     * @return array
     */
    public function parseFM($content, $fmParser)
    {
        $properties = [];

        // parse front matter
        preg_match(
            '/'.self::FM_PATTERN.'/s',
            $content,
            $matches
        );
        // if not front matter, set 'content' property only
        if (!$matches) {
            $properties['content'] = $content;

            return $properties;
        }
        // parse front matter
        /* @var $parser \PHPoole\Parser\ParserInterface */
        $parser = new $fmParser();
        $properties = $parser->parse(trim($matches[1]));
        $properties['content'] = trim($matches[2]);

        return $properties;
    }
}
