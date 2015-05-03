<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Page;

use Symfony\Component\Finder\SplFileInfo;

/**
 * Class Parser
 * @package PHPoole\Page
 */
class Parser
{
    /**
     * @var SplFileInfo
     */
    protected $file;
    /**
     * @var string
     */
    protected $frontmatter;
    /**
     * @var string
     */
    protected $body;

    /**
     * Constructor
     *
     * @param SplFileInfo $file
     */
    public function __construct(SplFileInfo $file)
    {
        $this->file = $file;
    }

    /**
     * Parse the contents of the file.
     *
     * Example:
     * <!--
     * title = Title
     * -->
     * Lorem Ipsum.
     *
     * @return $this
     * @throws \RuntimeException
     */
    public function parse()
    {
        if ($this->file->isFile()) {
            if (!$this->file->isReadable()) {
                throw new \RuntimeException('Cannot read file');
            }
            // parse front matter
            preg_match(
                '/^'
                . '(<!--|---|\+++){1}[\r\n|\n]*' // $matches[1] = front matter open
                . '(.*)[\r\n|\n]+'               // $matches[2] = front matter
                . '(-->|---|\+++){1}[\r\n|\n]*'  // $matches[3] = front matter close
                . '(.+)'                         // $matches[4] = body
                . '/s',
                $this->file->getContents(),
                $matches
            );
            // if not front matter, set body only
            if (!$matches) {
                $this->body = $this->file->getContents();
                return $this;
            }
            $this->frontmatter = trim($matches[2]);
            $this->body        = trim($matches[4]);
        }

        return $this;
    }

    /**
     * Get frontmatter
     *
     * @return string
     */
    public function getFrontmatter()
    {
        return $this->frontmatter;
    }

    /**
     * Get body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }
}
