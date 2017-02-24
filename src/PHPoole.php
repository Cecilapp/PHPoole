<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPoole\Collection\Collection as PageCollection;
use PHPoole\Exception\Exception;
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
     * Configuration.
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
     * Pages generators manager.
     *
     * @var GeneratorManager
     */
    protected $generatorManager;
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
     * Function used to return/display messages.
     *
     * @var \Closure
     */
    protected $messageCallback;
    /**
     * @var string
     */
    protected $log;

    /**
     * @var Logger
     */
    public $logger;

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

        //$output = "[%datetime%] %channel%.%level_name%: %message%\n";
        $output = "%message%\n";
        $formatter = new LineFormatter($output);

        $streamHandler = new StreamHandler('php://stdout', Logger::INFO);
        $streamHandler->setFormatter($formatter);

        $this->logger = new Logger('PHPoole');
        $this->logger->pushHandler($streamHandler);
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
     * @param PageCollection $pages
     */
    public function setPages(PageCollection $pages)
    {
        $this->logger->addDebug($pages->count());

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
     * @param \Closure|null $messageCallback
     */
    public function setMessageCallback($messageCallback = null)
    {
        if ($messageCallback === null) {
            $messageCallback = function ($code, $message = '', $itemsCount = 0, $itemsMax = 0, $verbose = true) {
                switch ($code) {
                    case 'CREATE':
                    case 'CONVERT':
                    case 'GENERATE':
                    case 'COPY':
                    case 'RENDER':
                    case 'TIME':
                        $log = sprintf("\n> %s\n", $message);
                        $this->addLog($log);
                        break;
                    case 'CREATE_PROGRESS':
                    case 'CONVERT_PROGRESS':
                    case 'GENERATE_PROGRESS':
                    case 'COPY_PROGRESS':
                    case 'RENDER_PROGRESS':
                        if ($itemsCount > 0 && $verbose !== false) {
                            $log = sprintf("  (%u/%u) %s\n", $itemsCount, $itemsMax, $message);
                            $this->addLog($log);
                        } else {
                            $log = sprintf("  %s\n", $message);
                            $this->addLog($log);
                        }
                        break;
                    case 'CREATE_ERROR':
                    case 'CONVERT_ERROR':
                    case 'GENERATE_ERROR':
                    case 'COPY_ERROR':
                    case 'RENDER_ERROR':
                        $log = sprintf("/!\ %s\n", $message);
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
        echo $this->log;
    }

    /**
     * Builds a new website.
     *
     * @return Result
     */
    public function build()
    {
        $steps = [];

        try {
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
            // execution time
            call_user_func_array($this->messageCallback, [
                'CREATE',
                sprintf('Execution time: %s seconds', round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 2)),
            ]);

            return new Result(true, $this->getLog());
        } catch (Exception $e) {
            $this->addLog($e->getMessage());

            return new Result(false, $this->getLog());
        }
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
