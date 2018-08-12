<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole;

use PHPoole\Collection\Collection as PageCollection;
use PHPoole\Generator\GeneratorManager;
use Symfony\Component\Finder\Finder;

/**
 * Class PHPoole.
 */
class PHPoole
{
    const VERSION = '1.1.x-dev';
    protected $version;
    /**
     * Steps that are processed by build().
     *
     * @var array
     *
     * @see build()
     */
    protected $steps = [
        'PHPoole\Step\LocateContent',
        'PHPoole\Step\CreatePages',
        'PHPoole\Step\ConvertPages',
        'PHPoole\Step\GeneratePages',
        'PHPoole\Step\GenerateMenus',
        'PHPoole\Step\CopyStatic',
        'PHPoole\Step\RenderPages',
    ];
    /**
     * Config.
     *
     * @var Config
     */
    protected $config;
    /**
     * Content iterator.
     *
     * @var Finder
     */
    protected $content;
    /**
     * Pages collection.
     *
     * @var PageCollection
     */
    protected $pages;
    /**
     * Collection of site menus.
     *
     * @var Collection\Menu\Collection
     */
    protected $menus;
    /**
     * Twig renderer.
     *
     * @var Renderer\Twig
     */
    protected $renderer;
    /**
     * @var \Closure
     */
    protected $messageCallback;
    /**
     * @var GeneratorManager
     */
    protected $generatorManager;
    /**
     * @var string
     */
    protected $log;

    /**
     * PHPoole constructor.
     *
     * @param Config|array|null $config
     * @param \Closure|null     $messageCallback
     */
    public function __construct($config = null, \Closure $messageCallback = null)
    {
        $this->setConfig($config);
        $this->config->setSourceDir(null)->setDestinationDir(null);
        $this->setMessageCallback($messageCallback);
    }

    /**
     * Creates a new PHPoole instance.
     *
     * @return PHPoole
     */
    public static function create()
    {
        $class = new \ReflectionClass(get_called_class());

        return $class->newInstanceArgs(func_get_args());
    }

    /**
     * Set config.
     *
     * @param Config|array|null $config
     *
     * @return $this
     */
    public function setConfig($config)
    {
        if (!$config instanceof Config) {
            $config = new Config($config);
        }
        if ($this->config !== $config) {
            $this->config = $config;
        }

        return $this;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Config::setSourceDir alias.
     *
     * @param $sourceDir
     *
     * @return $this
     */
    public function setSourceDir($sourceDir)
    {
        $this->config->setSourceDir($sourceDir);

        return $this;
    }

    /**
     * Config::setDestinationDir alias.
     *
     * @param $destinationDir
     *
     * @return $this
     */
    public function setDestinationDir($destinationDir)
    {
        $this->config->setDestinationDir($destinationDir);

        return $this;
    }

    /**
     * @param $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return Finder
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param $pages
     */
    public function setPages($pages)
    {
        $this->pages = $pages;
    }

    /**
     * @return PageCollection
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * @param $menus
     */
    public function setMenus($menus)
    {
        $this->menus = $menus;
    }

    /**
     * @return Collection\Menu\Collection
     */
    public function getMenus()
    {
        return $this->menus;
    }

    /**
     * @param \Closure|null $messageCallback
     */
    public function setMessageCallback($messageCallback = null)
    {
        if ($messageCallback === null) {
            $messageCallback = function ($code, $message = '', $itemsCount = 0, $itemsMax = 0) {
                switch ($code) {
                    case 'CREATE':
                    case 'CONVERT':
                    case 'GENERATE':
                    case 'MENU':
                    case 'COPY':
                    case 'RENDER':
                    case 'TIME':
                        $log = sprintf("%s\n", $message);
                        $this->addLog($log);
                        break;
                    case 'CREATE_PROGRESS':
                    case 'CONVERT_PROGRESS':
                    case 'GENERATE_PROGRESS':
                    case 'MENU_PROGRESS':
                    case 'COPY_PROGRESS':
                    case 'RENDER_PROGRESS':
                        if ($this->getConfig()->get('debug')) {
                            if ($itemsCount > 0) {
                                $log = sprintf("(%u/%u) %s\n", $itemsCount, $itemsMax, $message);
                                $this->addLog($log);
                            } else {
                                $log = sprintf("%s\n", $message);
                                $this->addLog($log);
                            }
                        }
                        break;
                    case 'CREATE_ERROR':
                    case 'CONVERT_ERROR':
                    case 'GENERATE_ERROR':
                    case 'MENU_ERROR':
                    case 'COPY_ERROR':
                    case 'RENDER_ERROR':
                        $log = sprintf(">> %s\n", $message);
                        $this->addLog($log);
                        break;
                }
            };
        }
        $this->messageCallback = $messageCallback;
    }

    /**
     * @return \Closure
     */
    public function getMessageCb()
    {
        return $this->messageCallback;
    }

    /**
     * @param $renderer
     */
    public function setRenderer($renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * @return Renderer\Twig
     */
    public function getRenderer()
    {
        return $this->renderer;
    }

    /**
     * @param $log
     *
     * @return string
     */
    public function addLog($log)
    {
        return $this->log .= $log;
    }

    /**
     * @return string
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * Display $log string.
     */
    public function showLog()
    {
        printf("\n%s", $this->log);
    }

    /**
     * Builds a new website.
     *
     * @param bool $verbose
     *
     * @return $this
     */
    public function build($verbose = false)
    {
        $steps = [];
        // init...
        foreach ($this->steps as $step) {
            /* @var $stepClass Step\StepInterface */
            $stepClass = new $step($this);
            $stepClass->init();
            $steps[] = $stepClass;
        }
        $this->steps = $steps;
        // ... and process!
        foreach ($this->steps as $step) {
            /* @var $step Step\StepInterface */
            $step->process();
        }
        // time
        call_user_func_array($this->messageCallback, [
            'CREATE',
            sprintf('Time: %s seconds', round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 2)),
        ]);

        if ($verbose) {
            $this->showLog();
        }

        return $this;
    }

    /**
     * Return version.
     *
     * @return string
     */
    public function getVersion()
    {
        if (!isset($this->version)) {
            try {
                $this->version = Util::runGitCommand('git describe --tags HEAD');
            } catch (\RuntimeException $exception) {
                $this->version = self::VERSION;
            }
        }

        return $this->version;
    }
}
