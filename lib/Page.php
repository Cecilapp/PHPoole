<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole;

use Symfony\Component\Finder\SplFileInfo;
use Cocur\Slugify\Slugify;

class Page implements \ArrayAccess
{
    private $slugify;

    protected $file;
    protected $fileExtension;
    protected $filePath;
    protected $fileId;
    protected $virtual;

    protected $id;
    protected $pathname;
    protected $name;
    protected $path;

    protected $title;
    protected $section;
    protected $layout = 'default.html';

    protected $frontmatter;
    protected $variables = [];
    protected $body;
    protected $html;

    /* @var $file SplFileInfo */
    public function __construct(SplFileInfo $file = null)
    {
        $this->file    = $file;
        $this->slugify = Slugify::create('/([^a-z0-9\/]|-)+/');

        if ($this->file instanceof SplFileInfo) {
            // file extension : md
            $this->fileExtension = pathinfo($this->file, PATHINFO_EXTENSION);
            // file path : Blog
            $this->filePath = str_replace(DIRECTORY_SEPARATOR, '/', $this->file->getRelativePath());
            // file id : Blog/Post 1
            $this->fileId = ($this->filePath ? $this->filePath . '/' : '') . basename($this->file->getBasename(), '.' . $this->fileExtension);
            // id : blog/post-1
            $this->id = $this->slugify->slugify($this->fileId);
            // pathname : blog/post-1
            $this->pathname = $this->slugify->slugify($this->fileId);
            // path : blog
            $this->path = $this->slugify->slugify($this->filePath);
            // name : post-1
            $this->name = $this->slugify->slugify(basename($this->file->getBasename(), '.' . $this->fileExtension));
            /**
             * frontmatter default values
             */
            // title : Post 1
            $this->title = basename($this->file->getBasename(), '.' . $this->fileExtension);
            // section : blog
            $this->section = explode('/', $this->path)[0];
        } else {
            $this->virtual = true;
        }
    }

    public function isVirtual() {
        return $this->virtual;
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
     * @return self
     * @throws \RuntimeException
     */
    private function parse()
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
            $this->frontmatter = $matches[2];
            $this->body        = $matches[4];
        }
        return $this;
    }

    public function process()
    {
        $this->parse();
        return $this;
    }

    public function setVariables($variables)
    {
        $this->variables = $variables;
        return $this;
    }
    public function getVariables()
    {
        return $this->variables;
    }
    public function setVariable($name, $value)
    {
        $this->variables[$name] = $value;
        return $this;
    }
    public function hasVariable($name)
    {
        return array_key_exists($name, $this->variables);
    }
    public function getVariable($name)
    {
        if ($this->hasVariable($name)) {
            return $this->variables[$name];
        }
        return null;
    }
    public function unVariable($name)
    {
        if ($this->hasVariable($name)) {
            unset($this->variables[$name]);
        }
    }

    public function offsetExists($offset)
    {
        //echo __METHOD__, " $offset\n";
        return $this->hasVariable($offset);
    }
    public function offsetGet($offset)
    {
        //echo __METHOD__, " $offset\n";
        return $this->getVariable($offset);
    }
    public function offsetSet($offset, $value)
    {
        //echo __METHOD__, " $offset\n";
        $this->setVariable($offset, $value);
    }
    public function offsetUnset($offset)
    {
        //echo __METHOD__, " $offset\n";
        $this->unVariable($offset);
    }

    /*
    public function __call($method, $params)
    {
        echo __METHOD__, "\n";

        if (method_exists($this, $method)) {
            return call_user_func_array(array($this, $method), $params);
        }
        if (substr($method, 0, 3) == 'set') {
            $property = strtolower(substr($method, 3));
            $value = $params[0];
            $this->setVariable($property, $value);
            return $this;
        }
        if (substr($method, 0, 3) == 'get') {
            $property = strtolower(substr($method, 3));
            return $this->getVariable($property);
        }
        if (property_exists($this, $method)) {
            return $this->$method;
        }
    }
    */

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    public function getName()
    {
        return $this->name;
    }
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }
    public function getPath()
    {
        return $this->path;
    }
    public function setPathname($pathname)
    {
        $this->pathname = $pathname;
        return $this;
    }
    public function getPathname()
    {
        return $this->pathname;
    }
    public function setSection($section)
    {
        $this->section = $section;
        return $this;
    }
    public function getSection()
    {
        if (empty($this->section) && !empty($this->path)) {
            $this->section = explode('/', $this->path)[0];
        }
        return $this->section;
    }
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }
    public function getTitle()
    {
        //echo __METHOD__, "\n";
        return $this->title;
    }
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
    public function getId()
    {
        return $this->id;
    }
    public function getFrontmatter()
    {
        return $this->frontmatter;
    }
    public function getBody()
    {
        return $this->body;
    }
    public function setHtml($html)
    {
        $this->html = $html;
        return $this;
    }
    public function getContent()
    {
        return $this->html;
    }
    public function setLayout($layout)
    {
        $this->layout = $layout;
        return $this;
    }
    public function getLayout()
    {
        return $this->layout;
    }
}