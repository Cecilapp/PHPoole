<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole;

use Dflydev\DotAccessData\Data;
use PHPoole\Exception\Exception;

/**
 * Class Config.
 */
class Config
{
    /**
     * Config.
     *
     * @var Data
     */
    protected $data;
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
     * Default data.
     *
     * @var array
     */
    protected static $defaultData = [
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
            'format' => [
                'text' => [
                    'ext' => ['text', 'txt'],
                    'frontmatter' => 'yaml',
                ],
                'markdown' => [
                    'ext' => ['md', 'markdown', 'mdown', 'mkdn', 'mkd'],
                    'parser' => 'PHPoole\Parser\Parsedown',
                    'frontmatter' => 'yaml',
                ],
                'textile' => [
                    'ext' => ['textile'],
                    'parser' => 'PHPoole\Parser\Textile',
                    'frontmatter' => 'yaml',
                ],
                'yaml' => [
                    'ext' => ['yaml', 'yaml'],
                    'parser' => 'PHPoole\Parser\SfYaml',
                ],
            ],
            'frontmatter' => [
                'yaml' => [
                    'parser' => 'PHPoole\Parser\SfYaml',
                ],
                'ini' => [
                    'parser' => 'PHPoole\Parser\Ini',
                ],
            ],
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
        'generators' => [
            10 => 'PHPoole\Generator\Section',
            20 => 'PHPoole\Generator\Taxonomy',
            30 => 'PHPoole\Generator\Homepage',
            40 => 'PHPoole\Generator\Pagination',
            50 => 'PHPoole\Generator\Alias',
            35 => 'PHPoole\Generator\ExternalBody',
        ],
    ];

    /**
     * Config constructor.
     *
     * @param Config|array|null $config
     */
    public function __construct($config = null)
    {
        $data = new Data(self::$defaultData);
        if ($config instanceof self) {
            $data->importData($config->getAll());
        } elseif (is_array($config)) {
            $data->import($config);
        }
        $this->setFromData($data);
    }

    /**
     * Set config data.
     *
     * @param Data $data
     *
     * @return $this
     */
    protected function setFromData(Data $data)
    {
        if ($this->data !== $data) {
            $this->data = $data;
        }

        return $this;
    }

    /**
     * Get config data.
     *
     * @return Data
     */
    public function getAll()
    {
        return $this->data;
    }

    /**
     * Get data as array.
     *
     * @return array
     */
    public function getAllAsArray()
    {
        return $this->data->export();
    }

    /**
     * Return a config value.
     *
     * @param string $key
     * @param string $default
     *
     * @return array|mixed|null
     */
    public function get($key, $default = '')
    {
        return $this->data->get($key, $default);
    }

    /**
     * @param $key
     * @return Data
     */
    public function getData($key)
    {
        return $this->data->getData($key);
    }

    /**
     * Set source directory.
     *
     * @param null $sourceDir
     *
     * @throws Exception
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
     * Get source directory.
     *
     * @return string
     */
    public function getSourceDir()
    {
        return $this->sourceDir;
    }

    /**
     * Set destination directory.
     *
     * @param null $destinationDir
     *
     * @throws Exception
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
     * Get destination directory.
     *
     * @return string
     */
    public function getDestinationDir()
    {
        return $this->destinationDir;
    }

    /**
     * Is config has a valid theme?
     *
     * @throws Exception
     *
     * @return bool
     */
    public function hasTheme()
    {
        if ($this->get('theme')) {
            if (!Util::getFS()->exists($this->getThemePath($this->get('theme')))) {
                throw new Exception(sprintf(
                    "Theme directory '%s/%s/layouts' not found!",
                    $this->getThemesPath(),
                    $this->get('theme')
                ));
            }

            return true;
        }

        return false;
    }

    /**
     * Path helpers.
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
     * @param string $theme
     * @param string $dir
     *
     * @return string
     */
    public function getThemePath($theme, $dir = 'layouts')
    {
        return $this->getSourceDir().'/'.$this->get('themes.dir').'/'.$theme.'/'.$dir;
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
