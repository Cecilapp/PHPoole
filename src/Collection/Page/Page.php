<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Collection\Page;

use PHPoole\Collection\Item;
use PHPoole\Page\NodeType;
use PHPoole\Page\VariableTrait;

/**
 * Class Page.
 */
class Page extends Item
{
    use VariableTrait;

    /**
     * @var array
     */
    protected $item;
    /**
     * @var string
     */
    protected $fileExtension;
    /**
     * @var string
     */
    protected $filePath;
    /**
     * @var string
     */
    protected $fileId;

    /**
     * @var string
     */
    protected $nodeType;

    /**
     * @var string
     */
    protected $id;
    /**
     * @var string
     */
    protected $pathname;
    /**
     * @var string
     */
    protected $path;
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $frontmatter;
    /**
     * @var string
     */
    protected $body;
    /**
     * @var string
     */
    protected $html;

    /**
     * Constructor.
     *
     * @param array|null $item
     */
    public function __construct($item = null)
    {
        /*
        [Blog/Post 1.md] => Array(
            [title] => Post 1
                [date] => 01/01/2015
                [tags] => Array(
                    [0] => tag-1
                    [1] => tag-2
                )
            [content] => Content.
        )
        */
        $this->item = $item;

        print_r($this->item = $item);

        explode()

        // file extension: "md"
        $this->fileExtension = pathinfo($this->item, PATHINFO_EXTENSION);
        // file path: "Blog"
        $this->filePath = str_replace(DIRECTORY_SEPARATOR, '/', $this->item->getRelativePath());
        // file id: "Blog/Post 1"
        $this->fileId = ($this->filePath ? $this->filePath.'/' : '')
            .basename($this->item->getBasename(), '.'.$this->fileExtension);
        /*
         * variables default values
         */
        // id - ie: "blog/post-1"
        $this->id = $this->urlize($this->fileId);
        // pathname - ie: "blog/post-1"
        $this->pathname = $this->urlize($this->fileId);
        // path - ie: "blog"
        $this->path = $this->urlize($this->filePath);
        // name - ie: "post-1"
        $this->name = $this->urlize(basename($this->item->getBasename(), '.'.$this->fileExtension));
        /*
         * front matter default values
         */
        // title - ie: "Post 1"
        $this->setTitle(basename($this->item->getBasename(), '.'.$this->fileExtension));
        // section - ie: "blog"
        $this->setSection(explode('/', $this->path)[0]);
        // date
        $this->setDate(filemtime($this->item->getPathname()));
        // permalink
        $this->setPermalink($this->pathname);

        parent::__construct($this->id);
    }

    /**
     * Set node type.
     *
     * @param string $nodeType
     *
     * @return self
     */
    public function setNodeType($nodeType)
    {
        $this->nodeType = new NodeType($nodeType);

        return $this;
    }

    /**
     * Get node type.
     *
     * @return string
     */
    public function getNodeType()
    {
        return $this->nodeType;
    }

    /**
     * Set name.
     *
     * @param $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set path.
     *
     * @param $path
     *
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set path name.
     *
     * @param string $pathname
     *
     * @return $this
     */
    public function setPathname($pathname)
    {
        $this->pathname = $pathname;

        return $this;
    }

    /**
     * Get path name.
     *
     * @return string
     */
    public function getPathname()
    {
        return $this->pathname;
    }

    /**
     * Set section.
     *
     * @param $section
     *
     * @return $this
     */
    public function setSection($section)
    {
        $this->setVariable('section', $section);

        return $this;
    }

    /**
     * Get section.
     *
     * @return mixed|false
     */
    public function getSection()
    {
        if (empty($this->getVariable('section')) && !empty($this->path)) {
            $this->setSection(explode('/', $this->path)[0]);
        }

        return $this->getVariable('section');
    }

    /**
     * Set title.
     *
     * @param $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->setVariable('title', $title);

        return $this;
    }

    /**
     * Get title.
     *
     * @return mixed|false
     */
    public function getTitle()
    {
        return $this->getVariable('title');
    }

    /**
     * Set date.
     *
     * @param $date
     *
     * @return $this
     */
    public function setDate($date)
    {
        $this->setVariable('date', $date);

        return $this;
    }

    /**
     * Get Date.
     *
     * @return \DateTime|false
     */
    public function getDate()
    {
        return $this->getVariable('date');
    }

    /**
     * Set permalink.
     *
     * @param $permalink
     *
     * @return $this
     */
    public function setPermalink($permalink)
    {
        $this->setVariable('permalink', $permalink);

        return $this;
    }

    /**
     * Get permalink.
     *
     * @return mixed|false
     */
    public function getPermalink()
    {
        if (empty($this->getVariable('permalink'))) {
            $this->setPermalink($this->getPathname());
        }

        return $this->getVariable('permalink');
    }

    /**
     * Get frontmatter.
     *
     * @return string
     */
    public function getFrontmatter()
    {
        return $this->frontmatter;
    }

    /**
     * Get body.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set HTML.
     *
     * @param string $html
     *
     * @return $this
     */
    public function setHtml($html)
    {
        $this->html = $html;

        return $this;
    }

    /**
     * Get HTML alias.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->html;
    }

    /**
     * Set layout.
     *
     * @param $layout
     *
     * @return $this
     */
    public function setLayout($layout)
    {
        $this->setVariable('layout', $layout);

        return $this;
    }

    /**
     * Get layout.
     *
     * @return mixed|false
     */
    public function getLayout()
    {
        return $this->getVariable('layout');
    }
}
