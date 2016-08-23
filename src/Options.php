<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole;

use Dflydev\DotAccessData\Data;

/**
 * Class Options.
 */
class Options
{
    /**
     * Options.
     *
     * @var Data
     */
    protected $options;
    /**
     * Source directory.
     *
     * @var string
     */
    protected $sourceDir;
    /**
     * Destination directory.
     *
     * @var string
     */
    protected $destinationDir;
    /**
     * Default options.
     *
     * @var array
     */
    protected static $defaultOptions = [
        'site' => [
            'title'       => 'PHPoole',
            'baseline'    => 'A PHPoole website',
            'baseurl'     => 'http://localhost:8000/',
            'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            'taxonomies'  => [
                'tags'       => 'tag',
                'categories' => 'category',
            ],
            'paginate' => [
                'max'  => 5,
                'path' => 'page',
            ],
            'date' => [
                'format'   => 'j F Y',
                'timezone' => 'Europe/Paris',
            ],
        ],
        'content' => [
            'dir' => 'content',
            'ext' => 'md',
        ],
        'frontmatter' => [
            'format' => 'yaml',
        ],
        'body' => [
            'format' => 'md',
        ],
        'static' => [
            'dir' => 'static',
        ],
        'layouts' => [
            'dir' => 'layouts',
        ],
        'output' => [
            'dir'      => '_site',
            'filename' => 'index.html',
        ],
        'themes' => [
            'dir' => 'themes',
        ],
    ];

    /**
     * Options constructor.
     *
     * @param Options|array|null $options
     */
    public function __construct($options)
    {
        $data = new Data(self::$defaultOptions);
        if ($options instanceof Options) {
            $data->importData($options->getAll());
        } elseif (is_array($options)) {
            $data->import($options);
        }
        $this->setFromData($data);
    }

    /**
     * Set options.
     *
     * @param Data $data
     *
     * @return $this
     */
    public function setFromData(Data $data)
    {
        if ($this->options !== $data) {
            $this->options = $data;
        }

        return $this;
    }

    /**
     * Get options.
     *
     * @return Data
     */
    public function getAll()
    {
        if (is_null($this->options)) {
            $this->setFromData(new Data());
        }

        return $this->options;
    }

    /**
     * Get options as array.
     *
     * @return array
     */
    public function getAllAsArray()
    {
        return $this->options->export();
    }

    /**
     * return an option value.
     *
     * @param string $key
     * @param string $default
     *
     * @return array|mixed|null
     */
    public function get($key, $default = '')
    {
        return $this->options->get($key, $default);
    }

    /**
     * @param null $sourceDir
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function setSourceDir($sourceDir = null)
    {
        if ($sourceDir === null) {
            $sourceDir = getcwd();
        }
        if (!is_dir($sourceDir)) {
            throw new \InvalidArgumentException(sprintf("'%s' is not a valid source directory.", $sourceDir));
        }
        $this->sourceDir = $sourceDir;

        return $this;
    }

    /**
     * @return string
     */
    public function getSourceDir()
    {
        return $this->sourceDir;
    }

    /**
     * @param null $destinationDir
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function setDestinationDir($destinationDir = null)
    {
        if ($destinationDir === null) {
            $destinationDir = $this->sourceDir;
        }
        if (!is_dir($destinationDir)) {
            throw new \InvalidArgumentException(sprintf("'%s' is not a valid destination directory.", $destinationDir));
        }
        $this->destinationDir = $destinationDir;

        return $this;
    }

    /**
     * @return string
     */
    public function getDestinationDir()
    {
        return $this->destinationDir;
    }

    /**
     * Path helpers
     */

    /**
     * @return string
     */
    public function getContentPath()
    {
        return $this->getSourceDir().'/'.$this->get('content.dir');
    }
    /**
     * @return string
     */
    public function getLayoutsPath()
    {
        return $this->getSourceDir().'/'.$this->get('layouts.dir');
    }
    /**
     * @return string
     */
    public function getThemesPath()
    {
        return $this->getSourceDir().'/'.$this->get('themes.dir');
    }
    /**
     * @return string
     */
    public function getThemePath($theme, $dir = 'layouts')
    {
        return $this->getSourceDir().'/'.$this->get('themes.dir')."/$theme/$dir";
    }
    /**
     * @return string
     */
    public function getOutputPath()
    {
        return $this->getSourceDir().'/'.$this->get('output.dir');
    }
    /**
     * @return string
     */
    public function getStaticPath()
    {
        return $this->getSourceDir().'/'.$this->get('static.dir');
    }
}
