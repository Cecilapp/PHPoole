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
                'taxonomies' => [
                    'tags'       => 'tag',
                    'categories' => 'category'
                ]
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
        $this->buildTaxonomies();
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
        // @todo taxonomy?
    }

    /**
     * Adds homepage to collection
     */
    protected function addHomePage()
    {
        if (!$this->pageCollection->has('index')) {
            $homePage = new Page();
            $homePage->setId('homepage')
                ->setTitle('Homepage')
                ->setNodeType('homepage')
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
                if (!$this->pageCollection->has("$section")) {
                    $page = (new Page())
                        ->setId("$section/index")
                        ->setPathname($section)
                        ->setTitle(ucfirst($section))
                        ->setNodeType('list')
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
     * Builds taxonomies
     */
    protected function buildTaxonomies()
    {
        $siteTaxonomies = [];
        if (array_key_exists('taxonomies', $this->getOptions()['site'])) {
            $taxonomies = $this->getOptions()['site']['taxonomies'];
            /* @var $page Page */
            foreach($this->pageCollection as $page) {
                /**
                 * ex:
                 * taxonomies:
                 *     tags: tag
                 *     categories: category
                 */
                foreach($taxonomies as $plural => $singular) {
                    if ($page->getVariable($plural) != null) {
                        /**
                         * List
                         * ex:
                         * tags: [Tag 1, Tag 2]
                         */
                        if (is_array($page->getVariable($plural))) {
                            foreach($page->getVariable($plural) as $term) {
                                $siteTaxonomies[$plural][$term][] = $page;
                            }
                        /**
                         * Unique
                         * ex:
                         * categories: Category
                         */
                        } else {
                            $siteTaxonomies[$plural][$page->getVariable($plural)][] = $page;
                        }
                    }
                }
            }
            /**
             * ex:
             * [
             *   [tags] =>
             *     [Tag 1] =>
             *       [0] => Page object
             *       [1] => Page object
             *       ...
             * ]
             */
            //print_r($siteTaxonomies);
            //die();
            foreach($siteTaxonomies as $plural => $terms) {
                // create $plural/$term pages (list of pages)
                foreach($terms as $term => $pages) {
                    $page = (new Page())
                        ->setId(Page::urlize("$plural/$term"))
                        ->setPathname(Page::urlize("$plural/$term"))
                        ->setTitle($term)
                        ->setNodeType('taxonomy')
                        ->setVariable('singular', $taxonomies[$plural])
                        ->setVariable('list', $pages);
                    // tmp: add to 'main' menu
                    if ($plural == 'categories') {
                        $page->setVariable('menu', [
                            'main' => ['weight' => 200]
                        ]);
                    }
                    //
                    $this->pageCollection->add($page);
                }
                // create $plural pages (list of terms)
                $page = (new Page())
                    ->setId(strtolower($plural))
                    ->setPathname(strtolower($plural))
                    ->setTitle($plural)
                    ->setNodeType('terms')
                    ->setVariable('plural', $plural)
                    ->setVariable('singular', $taxonomies[$plural])
                    ->setVariable('terms', $terms);
                // add page only if a template exist
                try {
                    $this->layoutFallback($page);
                    $this->pageCollection->add($page);
                } catch (\Exception $e) {
                    // do not add page
                    unset($page);
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
     * Pages rendering from pages collections + twig
     */
    protected function renderPages()
    {
        $dir = $this->destDir . '/' . $this->getOptions()['output']['dir'];
        $renderer = new Renderer\Twig($this->sourceDir . '/' . $this->getOptions()['layout']['dir']);

        $this->filesystem->mkdir($dir);
        /* @var $page Page */
        foreach($this->pageCollection as $page) {
            $renderer->render($this->layoutFallback($page), [
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

        // copy static dir if exist
        $staticDir = $this->sourceDir . '/' . $this->getOptions()['static']['dir'];
        if ($this->filesystem->exists($staticDir)) {
            $this->filesystem->mirror($staticDir, $dir, null, ['override' => true]);
        }

        return true;
    }

    /**
     * Layout file fall-back
     *
     * @param Page $page
     * @return string
     * @throws \Exception
     */
    protected function layoutFallback(Page $page)
    {
        $layout = 'page.html';
        $layoutsDir = $this->sourceDir . '/' . $this->getOptions()['layout']['dir'];

        switch ($page->getNodeType()) {
            case 'homepage':
                $layouts = [
                    'index.html',
                    '_default/list.html',
                    '_default/page.html',
                ];
                break;
            case 'list':
                $layouts = [
                    '_default/section.html',
                    '_default/list.html',
                ];
                // 'section/$section.html'
                if ($page->getSection() != null) {
                    $layouts = array_merge(["section/{$page->getSection()}.html"], $layouts);
                }
                break;
            case 'taxonomy':
                $layouts = [
                    '_default/taxonomy.html',
                    '_default/list.html',
                ];
                // 'taxonomy/$singular.html'
                if ($page->getVariable('singular') != null) {
                    $layouts = array_merge(["taxonomy/{$page->getVariable('singular')}.html"], $layouts);
                }
                break;
            case 'terms':
                $layouts = [
                    '_default/terms.html',
                ];
                // 'taxonomy/$singular.terms.html'
                if ($page->getVariable('singular') != null) {
                    $layouts = array_merge(["taxonomy/{$page->getVariable('singular')}.terms.html"], $layouts);
                }
                break;
            case 'page':
            default:
                $layouts = [
                    '_default/page.html',
                ];
                // '$section/page.html'
                if ($page->getSection() != null) {
                    $layouts = array_merge(["{$page->getSection()}/page.html"], $layouts);
                    // '$section/$layout.html'
                    if ($page->getLayout() != null) {
                        $layouts = array_merge(["{$page->getSection()}/{$page->getLayout()}.html"], $layouts);
                    }
                    // 'page.html'
                } else {
                    $layouts = array_merge(['page.html'], $layouts);
                    // '$layout.html'
                    if ($page->getLayout() != null) {
                        $layouts = array_merge(["{$page->getLayout()}.html"], $layouts);
                    }
                }
        }

        foreach($layouts as $layout) {
            if ($this->filesystem->exists($layoutsDir . '/' . $layout)) {
                return $layout;
            }
        }
        throw new \Exception(sprintf("Layout '%s' not found!", $layout));
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