<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;
use Parsedown;
use PHPoole\Plugin\PluginInterface;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventsCapableInterface;

/**
 * Class PHPoole
 * @package PHPoole
 */
class PHPoole implements EventsCapableInterface
{
    const VERSION = '2.0.0-dev';

    /**
     * @var string
     */
    protected $sourceDir;

    /**
     * @var string
     */
    protected $destDir;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var Finder
     */
    protected $contentIterator;

    /**
     * @var PageCollection
     */
    protected $pageCollection;

    /**
     * @var array
     */
    protected $site;

    /**
     * @var array
     */
    protected $sections;

    /**
     * @var array
     */
    protected $menus;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * The EventManager
     *
     * @var null|EventManager
     */
    protected $events = null;

    /**
     * The plugin registry
     *
     * @var \SplObjectStorage
     */
    protected $pluginRegistry;

    /**
     * Constructor
     *
     * @param null $sourceDir
     * @param null $destDir
     * @param array $options
     */
    public function __construct($sourceDir = null, $destDir = null, $options = array())
    {
        if ($sourceDir == null) {
            $this->sourceDir = __DIR__;
        } else {
            $this->sourceDir = $sourceDir;
        }
        if ($destDir == null) {
            $this->destDir = $this->sourceDir;
        } else {
            $this->destDir = $destDir;
        }

        $options = array_replace_recursive([
            'site' => [
                'title'   => "PHPoole's website",
                'baseurl' => 'http://localhost:63342/PHPoole-library/demo/site/',
            ],
            'content' => [
                'dir' => 'content',
                'ext' => 'md'
            ],
            'frontmatter' => [
                'format' => 'yaml'
            ],
            'body' => [
                'format' => 'md'
            ],
            'static' => [
                'dir' => 'static'],
            'layout' =>  [
                'dir'     => 'layouts',
                'default' => 'default.html'
            ],
            'output' => [
                'dir'      => 'site',
                'filename' => 'index.html'
            ],
        ], $options);
        if ($options) {
            $this->setOptions($options);
        }

        $this->filesystem = new Filesystem();

        //$this->addPlugin(new Plugin\Example);
    }

    /**
     * Creates a new PHPoole instance
     *
     * @return PHPoole
     */
    public static function create()
    {
        //return new static();
        $r = new \ReflectionClass(get_called_class());
        return $r->newInstanceArgs(func_get_args());
    }

    /**
     * Set options
     *
     * @param  array $options
     * @return self
     * @see    getOptions()
     */
    public function setOptions($options)
    {
        if ($this->options !== $options) {
            $this->options = $options;
            $this->trigger('options', $options);
        }
        return $this;
    }

    /**
     * Get options
     *
     * @return array
     * @see    setOptions()
     */
    public function getOptions()
    {
        if (!$this->options) {
            $this->setOptions(array());
        }
        return $this->options;
    }

    /**
     * Builds a new website
     */
    public function build()
    {
        $this->locateContent();
        $this->buildPagesFromContent();
        $this->convertPages();
        $this->addVirtualPages();
        $this->buildMenus();
        $this->buildSiteVars();
        $this->renderPages();
    }

    /**
     * Locates content
     */
    protected function locateContent()
    {
        try {
            $dir = $this->sourceDir . '/' . $this->getOptions()['content']['dir'];
            $params = compact('dir');
            $this->triggerPre(__FUNCTION__, $params);
            $this->contentIterator = Finder::create()
                ->files()
                ->in($params['dir'])
                ->name('*.' . $this->getOptions()['content']['ext']);
            $this->triggerPost(__FUNCTION__, $params);
            if ($this->contentIterator instanceof Finder) {
                throw new \Exception('Result must be an instance of Finder.');
            }
        } catch (\Exception $e) {
            $params = compact('dir', 'e');
            $this->triggerException(__FUNCTION__, $params);
        }
    }

    /**
     * Builds pages collection from content iterator
     */
    protected function buildPagesFromContent()
    {
        $this->pageCollection = new PageCollection();
        /* @var $file SplFileInfo */
        /* @var $page Page */
        foreach($this->contentIterator as $file) {
            $page = (new Page($file))
                ->parse();
            $this->pageCollection->add($page);
        }
    }

    /**
     * Converts page content
     * * Yaml frontmatter -> PHP array
     * * Mardown body -> HTML
     */
    protected function convertPages()
    {
        /* @var $page Page */
        foreach($this->pageCollection as $page) {
            if (!$page->isVirtual()) {
                // converts frontmatter
                switch ($this->getOptions()['frontmatter']['format']) {
                    case 'ini':
                        $variables = parse_ini_string($page->getFrontmatter());
                        break;
                    case 'yaml':
                    default:
                        $variables = Yaml::parse($page->getFrontmatter());
                }
                // converts body
                $html = (new Parsedown())->text($page->getBody());
                // setting page properties
                if (array_key_exists('title', $variables)) {
                    $page->setTitle($variables['title']);
                    unset($variables['title']);
                }
                if (array_key_exists('section', $variables)) {
                    $page->setSection($variables['section']);
                    unset($variables['section']);
                }
                $page->setHtml($html);
                // setting page variables
                $page->setVariables($variables);
                $this->pageCollection->replace($page->getId(), $page);
            }
        }
    }

    /**
     * Adds virtual pages to collection
     */
    protected function addVirtualPages()
    {
        $this->addHomePage();
        $this->addSectionPages();
        // @todo move to a plugin
        //$this->addTagsPage();
    }

    /**
     * Adds homepage to collection
     */
    protected function addHomePage()
    {
        if (!$this->pageCollection->has('index')) {
            $homePage = new Page();
            $homePage->setId('index')
                ->setPathname('')
                ->setTitle('Homepage')
                ->setLayout('index.html')
                ->setVariable('menu', [
                    'main' => ['weight' => 1]
                ]);
            $this->pageCollection->add($homePage);
        }
    }

    /**
     * Adds section pages to collection
     */
    protected function addSectionPages()
    {
        /* @var $page Page */
        foreach($this->pageCollection as $page) {
            if ($page->getSection() != '') {
                $this->sections[$page->getSection()][] = $page;
            }
        }
        if (!empty($this->sections)) {
            foreach ($this->sections as $section => $pageObject) {
                if (!$this->pageCollection->has("$section/index")) {
                    $page = (new Page())
                        ->setId("$section/index")
                        ->setPathname($section)
                        ->setTitle(ucfirst($section))
                        ->setLayout('list.html')
                        ->setVariable('list', $pageObject)
                        ->setVariable('menu', [
                            'main' => ['weight' => 100]
                        ]);
                    $this->pageCollection->add($page);
                }
            }
        }
    }

    /**
     * Builds menus
     */
    protected function buildMenus()
    {
        /* @var $page Page */
        foreach($this->pageCollection as $page) {
            if (!is_null($page->getVariable('menu'))) {
                $menu = $page->getVariable('menu');
                // single
                if (is_string($menu) && !empty($menu)) {
                    $this->menus[$menu][$page->getId()] = [
                        'id'   => $page->getId(),
                        'name' => $page->getTitle(),
                        'url'  => $page->getPathname(),
                        'menu' => $menu,
                    ];
                }
                // multiple
                if (is_array($menu) && !empty($menu)) {
                    foreach($menu as $name => $value) {
                        $this->menus[$name][$page->getId()] = [
                            'id'     => $page->getId(),
                            'name'   => $page->getTitle(),
                            'url'    => $page->getPathname(),
                            'menu'   => $name,
                            'weight' => $value['weight'],
                        ];
                    }
                }
            }
        }
    }

    /**
     * Builds site variables
     */
    protected function buildSiteVars()
    {
        $this->site = array_merge(
            $this->getOptions()['site'],
            ['menus' => $this->menus]
        );
    }

    /**
     * Adds tags pages
     * @todo move to a plugin
     */
    protected function addTagsPage()
    {
        $tags = [];
        /* @var $page Page */
        foreach($this->pageCollection as $page) {
            $tags[] = $page->getVariable('tags');
        }
        $tags = array_unique($tags);
        /* @var $tagsPage Page */
        $tagsPage = new Page();
        $tagsPage->setId('tags')
            ->setPathname('tags')
            ->setTitle('Tags list')
            ->setVariable('tagslist', $tags)
            ->setLayout('tags.html');
        $this->pageCollection->add($tagsPage);
    }

    /**
     * Pages rendering from pages collections + twig
     */
    protected function renderPages()
    {
        $dir = $this->destDir . '/' . $this->getOptions()['output']['dir'];
        $renderer = new Renderer\Twig($this->sourceDir . '/' . $this->getOptions()['layout']['dir']);

        $this->filesystem->mkdir($dir);
        /* @var $page Page */
        foreach($this->pageCollection as $page) {
            $renderer->render($page->getLayout(), [
                'site'    => $this->site,
                'page'    => $page,
                'phpoole' => [
                    'version'   => self::VERSION,
                    'poweredby' => 'PHPoole v' . self::VERSION,
                ]
            ]);
            // create an index/list from on a content file instead of a virtual page
            if ($page->getName() == 'index') {
                $pathname = $dir . '/' . $page->getPath() . '/' . $this->getOptions()['output']['filename'];
            } else {
                $pathname = $dir . '/' . $page->getPathname() . '/' . $this->getOptions()['output']['filename'];
            }
            $renderer->save($pathname);
        }

        // copy static
        $this->filesystem->mirror(
            $this->sourceDir . '/' . $this->getOptions()['static']['dir'],
            $dir,
            null,
            ['override' => true]
        );

        return true;
    }


    /**
     * Plugin logic
     */

    /**
     * Get the event manager
     *
     * @return EventManager
     */
    public function getEventManager()
    {
        if ($this->events === null) {
            $this->events = new EventManager(array(__CLASS__, get_class($this)));
        }
        return $this->events;
    }

    /**
     * Trigger event
     *
     * @param $eventName
     * @param array $params
     */
    protected function trigger($eventName, array $params = array())
    {
        $params = $this->getEventManager()->prepareArgs($params);
        $this->getEventManager()->trigger($eventName, $this, $params);
    }

    /**
     * Trigger "pre" event
     *
     * @param $eventName
     * @param array $params
     * @see   trigger()
     */
    protected function triggerPre($eventName, array $params = array())
    {
        $this->trigger($eventName . '.pre', $params);
    }

    /**
     * Trigger "post" event
     *
     * @param $eventName
     * @param array $params
     * @see   trigger()
     */
    protected function triggerPost($eventName, array $params = array())
    {
        $this->trigger($eventName . '.post', $params);
    }

    /**
     * Trigger "exception" event
     *
     * @param $eventName
     * @param array $params
     * @see   trigger()
     */
    protected function triggerException($eventName, array $params = array())
    {
        $this->trigger($eventName . '.exception', $params);
    }

    /**
     * Check if a plugin is registered
     *
     * @param  PluginInterface $plugin
     * @return bool
     */
    public function hasPlugin(PluginInterface $plugin)
    {
        $registry = $this->getPluginRegistry();
        return $registry->contains($plugin);
    }

    /**
     * Register a plugin
     *
     * @param  PluginInterface $plugin
     * @param  int             $priority
     * @return PHPoole
     * @throws \LogicException
     */
    public function addPlugin(PluginInterface $plugin, $priority = 1)
    {
        $registry = $this->getPluginRegistry();
        if ($registry->contains($plugin)) {
            throw new \LogicException(sprintf(
                'Plugin of type "%s" already registered',
                get_class($plugin)
            ));
        }
        $plugin->attach($this->getEventManager(), $priority);
        $registry->attach($plugin);
        return $this;
    }

    /**
     * Remove an already registered plugin
     *
     * @param  PluginInterface $plugin
     * @return self
     * @throws \LogicException
     */
    public function removePlugin(PluginInterface $plugin)
    {
        $registry = $this->getPluginRegistry();
        if ($registry->contains($plugin)) {
            $plugin->detach($this->getEventManager());
            $registry->detach($plugin);
        }
        return $this;
    }

    /**
     * Return registry of plugins
     *
     * @return \SplObjectStorage
     */
    public function getPluginRegistry()
    {
        if (!$this->pluginRegistry instanceof \SplObjectStorage) {
            $this->pluginRegistry = new \SplObjectStorage();
        }
        return $this->pluginRegistry;
    }
}