<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole;

use PHPoole\Converter\Converter;
use PHPoole\Exception\Exception;
use PHPoole\Generator\GeneratorManager;
use PHPoole\Page\Collection as PageCollection;
use PHPoole\Page\Page;
use PHPoole\Renderer\Layout;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

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
        'locateContent',
        'createPages',
        'convertPages',
        'generatePages',
        'generateMenus',
        'copyStatic',
        'renderPages',
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
     * @var Menu\Collection
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
                        printf("\n> %s\n", $message);
                        break;
                    case 'CREATE_PROGRESS':
                    case 'CONVERT_PROGRESS':
                    case 'GENERATE_PROGRESS':
                    case 'COPY_PROGRESS':
                    case 'RENDER_PROGRESS':
                        if ($itemsCount > 0 && $verbose !== false) {
                            printf("  (%u/%u) %s\n", $itemsCount, $itemsMax, $message);
                        } else {
                            printf("  %s\n", $message);
                        }
                        break;
                }
            };
        }
        $this->messageCallback = $messageCallback;
    }

    /**
     * Builds a new website.
     */
    public function build()
    {
        foreach ($this->steps as $step) {
            $this->$step();
        }
        // time
        call_user_func_array($this->messageCallback, [
            'CREATE',
            sprintf('Time: %s seconds', round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 2)),
        ]);
    }

    /**
     * Locates content.
     *
     * @see build()
     */
    protected function locateContent()
    {
        try {
            $this->content = Finder::create()
                ->files()
                ->in($this->config->getContentPath())
                ->name('/\.('.implode('|', $this->config->get('content.ext')).')$/');
            if (!$this->content instanceof Finder) {
                throw new Exception(__FUNCTION__.': result must be an instance of Symfony\Component\Finder.');
            }
        } catch (Exception $e) {
            echo $e->getMessage()."\n";
        }
    }

    /**
     * Create Pages collection from content iterator.
     *
     * @see build()
     */
    protected function createPages()
    {
        $this->pages = new PageCollection();
        if (count($this->content) <= 0) {
            return;
        }
        call_user_func_array($this->messageCallback, ['CREATE', 'Creating pages']);
        $max = count($this->content);
        $count = 0;
        /* @var $file SplFileInfo */
        foreach ($this->content as $file) {
            $count++;
            /* @var $page Page */
            $page = (new Page($file))->parse();
            $this->pages->add($page);
            $message = $page->getName();
            call_user_func_array($this->messageCallback, ['CREATE_PROGRESS', $message, $count, $max]);
        }
    }

    /**
     * Converts content of all pages.
     *
     * @see convertPage()
     * @see build()
     */
    protected function convertPages()
    {
        if (count($this->pages) <= 0) {
            return;
        }
        call_user_func_array($this->messageCallback, ['CONVERT', 'Converting pages']);
        $max = count($this->pages);
        $count = 0;
        $countError = 0;
        /* @var $page Page */
        foreach ($this->pages as $page) {
            if (!$page->isVirtual()) {
                $count++;
                if (false !== $convertedPage = $this->convertPage($page, $this->config->get('frontmatter.format'))) {
                    $this->pages->replace($page->getId(), $convertedPage);
                } else {
                    $countError++;
                }
                $message = $page->getName();
                call_user_func_array($this->messageCallback, ['CONVERT_PROGRESS', $message, $count, $max]);
            }
        }
        if ($countError > 0) {
            call_user_func_array($this->messageCallback, ['CONVERT_PROGRESS', sprintf('Errors: %s', $countError)]);
        }
    }

    /**
     * Converts page content:
     * * Yaml frontmatter to PHP array
     * * Markdown body to HTML.
     *
     * @param Page   $page
     * @param string $format
     *
     * @return Page
     */
    public function convertPage(Page $page, $format = 'yaml')
    {
        $converter = new Converter();

        // converts frontmatter
        try {
            $variables = $converter->convertFrontmatter($page->getFrontmatter(), $format);
        } catch (Exception $e) {
            $message = sprintf("> Unable to convert frontmatter of '%s': %s", $page->getId(), $e->getMessage());
            call_user_func_array($this->messageCallback, ['CONVERT_PROGRESS', $message]);

            return false;
        }
        $page->setVariables($variables);

        // converts body
        $html = $converter->convertBody($page->getBody());
        $page->setHtml($html);

        return $page;
    }

    /**
     * Generates virtual pages.
     *
     * @see build()
     */
    protected function generatePages()
    {
        $generators = $this->config->get('generators');
        $this->generatorManager = new GeneratorManager();
        array_walk($generators, function ($generator, $priority) {
            if (!class_exists($generator)) {
                $message = sprintf("> Unable to load generator '%s'", $generator);
                call_user_func_array($this->messageCallback, ['GENERATE_PROGRESS', $message]);

                return;
            }
            $this->generatorManager->addGenerator(new $generator($this->config), $priority);
        });
        call_user_func_array($this->messageCallback, ['GENERATE', 'Generating pages']);
        $this->pages = $this->generatorManager->process($this->pages, $this->messageCallback);
    }

    /**
     * Generates menus.
     *
     * @see build()
     */
    protected function generateMenus()
    {
        $this->menus = new Menu\Collection();

        $this->generateMenusCollect();

        /*
         * Removing/adding/replacing menus entries from config array
         * ie:
         * ['site' => [
         *     'menu' => [
         *         'main' => [
         *             'test' => [
         *                 'id'     => 'test',
         *                 'name'   => 'Test website',
         *                 'url'    => 'http://test.org',
         *                 'weight' => 999,
         *             ],
         *         ],
         *     ],
         * ]]
         */
        if (!empty($this->config->get('site.menu'))) {
            foreach ($this->config->get('site.menu') as $name => $entry) {
                /* @var $menu Menu\Menu */
                $menu = $this->menus->get($name);
                foreach ($entry as $property) {
                    // remove disable entries
                    if (isset($property['disabled']) && $property['disabled']) {
                        if (isset($property['id']) && $menu->has($property['id'])) {
                            $menu->remove($property['id']);
                        }
                        continue;
                    }
                    // add new entries
                    $item = (new Menu\Entry($property['id']))
                        ->setName($property['name'])
                        ->setUrl($property['url'])
                        ->setWeight($property['weight']);
                    $menu->add($item);
                }
            }
        }
    }

    /**
     * Collects pages with menu entry.
     */
    protected function generateMenusCollect()
    {
        foreach ($this->pages as $page) {
            /* @var $page Page */
            if (!empty($page['menu'])) {
                /*
                 * Single case
                 * ie:
                 * menu: main
                 */
                if (is_string($page['menu'])) {
                    $item = (new Menu\Entry($page->getId()))
                        ->setName($page->getTitle())
                        ->setUrl($page->getPermalink());
                    /* @var $menu Menu\Menu */
                    $menu = $this->menus->get($page['menu']);
                    $menu->add($item);
                } else {
                    /*
                     * Multiple case
                     * ie:
                     * menu:
                     *     main:
                     *         weight: 1000
                     *     other
                     */
                    if (is_array($page['menu'])) {
                        foreach ($page['menu'] as $name => $value) {
                            $item = (new Menu\Entry($page->getId()))
                                ->setName($page->getTitle())
                                ->setUrl($page->getPermalink())
                                ->setWeight($value['weight']);
                            /* @var $menu Menu\Menu */
                            $menu = $this->menus->get($name);
                            $menu->add($item);
                        }
                    }
                }
            }
        }
    }

    /**
     * Copy static directory content to site root.
     *
     * @see build()
     */
    protected function copyStatic()
    {
        call_user_func_array($this->messageCallback, ['COPY', 'Copy static files']);
        // copy theme static dir if exists
        if ($this->config->hasTheme()) {
            $themeStaticDir = $this->config->getThemePath($this->config->get('theme'), 'static');
            if (Util::getFS()->exists($themeStaticDir)) {
                Util::getFS()->mirror($themeStaticDir, $this->config->getOutputPath(), null, ['override' => true]);
            }
        }
        // copy static dir if exists
        $staticDir = $this->config->getStaticPath();
        if (Util::getFS()->exists($staticDir)) {
            $finder = new Finder();
            $finder->files()->filter(function (\SplFileInfo $file) {
                return !(is_array($this->config->get('static.exclude'))
                    && in_array($file->getBasename(), $this->config->get('static.exclude')));
            })->in($staticDir);
            Util::getFS()->mirror($staticDir, $this->config->getOutputPath(), $finder, ['override' => true]);
        }
        call_user_func_array($this->messageCallback, ['COPY_PROGRESS', 'Done']);
    }

    /**
     * Pages rendering:
     * 1. Iterates Pages collection
     * 2. Applies Twig templates
     * 3. Saves rendered files.
     *
     * @see renderPage()
     * @see build()
     */
    protected function renderPages()
    {
        $paths = [];

        // checks layouts dir
        if (!is_dir($this->config->getLayoutsPath()) && !$this->config->hasTheme()) {
            throw new Exception(sprintf("'%s' is not a valid layouts directory", $this->config->getLayoutsPath()));
        }

        // prepares renderer
        if (is_dir($this->config->getLayoutsPath())) {
            $paths[] = $this->config->getLayoutsPath();
        }
        if ($this->config->hasTheme()) {
            $paths[] = $this->config->getThemePath($this->config->get('theme'));
        }
        $this->renderer = new Renderer\Twig($paths, $this->config);

        // adds global variables
        $this->renderer->addGlobal('site', array_merge(
            $this->config->get('site'),
            ['menus' => $this->menus],
            ['pages' => $this->pages]
        ));
        $this->renderer->addGlobal('phpoole', [
            'url'       => 'http://phpoole.org/#v'.$this->getVersion(),
            'version'   => $this->getVersion(),
            'poweredby' => 'PHPoole-library v'.self::getVersion(),
        ]);

        // start rendering
        Util::getFS()->mkdir($this->config->getOutputPath());
        call_user_func_array($this->messageCallback, ['RENDER', 'Rendering pages']);
        $max = count($this->pages);
        $count = 0;
        /* @var $page Page */
        foreach ($this->pages as $page) {
            $count++;
            $pathname = $this->renderPage($page, $this->config->getOutputPath());
            $message = substr($pathname, strlen($this->config->getDestinationDir()) + 1);
            call_user_func_array($this->messageCallback, ['RENDER_PROGRESS', $message, $count, $max]);
        }
    }

    /**
     * Render a page.
     *
     * @param Page   $page
     * @param string $dir
     *
     * @throws Exception
     *
     * @see renderPages()
     *
     * @return string Path to the generated page
     */
    protected function renderPage(Page $page, $dir)
    {
        $this->renderer->render((new Layout())->finder($page, $this->config), ['page' => $page]);

        // force pathname of a (non virtual) node page
        if ($page->getName() == 'index') {
            $pathname = $dir.'/'.$page->getPath().'/'.$this->config->get('output.filename');
        // pathname of a (normal) page
        } else {
            if (empty(pathinfo($page->getPermalink(), PATHINFO_EXTENSION))) {
                $pathname = $dir.'/'.$page->getPermalink().'/'.$this->config->get('output.filename');
            } else {
                $pathname = $dir.'/'.$page->getPermalink();
            }
        }
        // remove unnecessary slashes
        $pathname = preg_replace('#/+#', '/', $pathname);

        $this->renderer->save($pathname);

        return $pathname;
    }

    /**
     * Return version.
     *
     * @return string
     */
    protected function getVersion()
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
