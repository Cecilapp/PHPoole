<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole;

use Dflydev\DotAccessData\Data;
use PHPoole\Converter\Converter;
use PHPoole\Page\Collection as PageCollection;
use PHPoole\Page\NodeTypeEnum;
use PHPoole\Page\Page;
use PHPoole\Plugin\PluginAwareTrait;
use PHPoole\Renderer\RendererInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Zend\EventManager\EventsCapableInterface;

/**
 * Class PHPoole.
 */
class PHPoole implements EventsCapableInterface
{
    use PluginAwareTrait;

    const VERSION = '1.1.x-dev';
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
    protected $destDir;
    /**
     * Array of options.
     *
     * @var array
     */
    protected $options;
    /**
     * Content iterator.
     *
     * @var Finder
     */
    protected $contentIterator;
    /**
     * Pages collection.
     *
     * @var PageCollection
     */
    protected $pageCollection;
    /**
     * Site variables.
     *
     * @var array
     */
    protected $site;
    /**
     * Array of site sections.
     *
     * @var array
     */
    protected $sections;
    /**
     * Collection of site menus.
     *
     * @var Collection\CollectionInterface
     */
    protected $menus;
    /**
     * Collection of taxonomies menus.
     *
     * @var Collection\CollectionInterface
     */
    protected $taxonomies;
    /**
     * Twig renderer.
     *
     * @var RendererInterface
     */
    protected $renderer;
    /**
     * The theme name.
     *
     * @var null
     */
    protected $theme = null;
    /**
     * Symfony\Component\Filesystem.
     *
     * @var Filesystem
     */
    protected $fs;

    /**
     * PHPoole constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        // backward compatibility
        $args = func_get_args();
        if (count($args) > 1) {
            $this->setSource($args[0]);
            $this->setDestination($args[1]);
            $options = $args[2];
        } else {
            $this->setSource(null);
            $this->setDestination(null);
        }

        $options = array_replace_recursive([
            'site' => [
                'title'       => 'PHPoole',
                'baseline'    => 'A PHPoole website',
                'baseurl'     => 'http://localhost:8000/',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                'taxonomies'  => [
                    'tags'       => 'tag',
                    'categories' => 'category',
                ],
                'paginate' => [
                    'max'  => 5,
                    'path' => 'page',
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
        ], $options);
        if (!empty($options)) {
            $this->setOptions($options);
        }

        $this->fs = new Filesystem();
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
     * @param null $sourceDir
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function setSource($sourceDir = null)
    {
        if ($sourceDir === null) {
            $sourceDir = getcwd();
        }
        if (!is_dir($sourceDir)) {
            throw new \Exception(sprintf("'%s' is not a valid source directory.", $sourceDir));
        }

        $this->sourceDir = $sourceDir;

        return $this;
    }

    /**
     * @param null $destDir
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function setDestination($destDir = null)
    {
        if ($destDir === null) {
            $destDir = $this->sourceDir;
        }
        if (!is_dir($destDir)) {
            throw new \Exception(sprintf("'%s' is not a valid destination directory.", $destDir));
        }

        $this->destDir = $destDir;

        return $this;
    }

    /**
     * Set options.
     *
     * @param array $options
     *
     * @return self
     *
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
     * Get options.
     *
     * @return null|array
     *
     * @see    setOptions()
     */
    public function getOptions()
    {
        if (is_null($this->options)) {
            $this->setOptions([]);
        }

        return $this->options;
    }

    /**
     * Get an option.
     *
     * @param string $key
     * @param string $default
     *
     * @return array|mixed|null
     *
     * @see    getOptions()
     */
    public function getOption($key, $default = '')
    {
        $data = new Data($this->getOptions());

        return $data->get($key, $default);
    }

    /**
     * Builds a new website.
     */
    public function build()
    {
        // locates content
        $this->locateContent();
        // creates Pages collection from content
        $this->createPagesFromContent();
        // converts Pages content
        $this->convertPages();
        // generates virtual content
        $this->generateSections();
        $this->generateTaxonomies();
        $this->generateHomepage();
        $this->generatesAliases();
        $this->generateMenus();
        // rendering
        $this->renderPages();
        // copies static files
        $this->copyStatic();
    }

    /**
     * Locates content.
     *
     * @see build()
     */
    protected function locateContent()
    {
        try {
            $dir = $this->sourceDir.'/'.$this->getOption('content.dir');
            $params = compact('dir');
            $this->triggerPre(__FUNCTION__, $params);
            $this->contentIterator = Finder::create()
                ->files()
                ->in($params['dir'])
                ->name('*.'.$this->getOption('content.ext'));
            $this->triggerPost(__FUNCTION__, $params);
            if (!$this->contentIterator instanceof Finder) {
                throw new \Exception(__FUNCTION__.': result must be an instance of Symfony\Component\Finder.');
            }
        } catch (\Exception $e) {
            $params = compact('dir', 'e');
            $this->triggerException(__FUNCTION__, $params);
            echo $e->getMessage()."\n";
        }
    }

    /**
     * Create Pages collection from content iterator.
     *
     * @see build()
     */
    protected function createPagesFromContent()
    {
        $this->pageCollection = new PageCollection();
        if (count($this->contentIterator) <= 0) {
            return;
        }
        /* @var $file SplFileInfo */
        /* @var $page Page */
        foreach ($this->contentIterator as $file) {
            $page = (new Page($file))
                ->parse();
            $this->pageCollection->add($page);
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
        if (count($this->pageCollection) <= 0) {
            return;
        }
        /* @var $page Page */
        foreach ($this->pageCollection as $page) {
            if (!$page->isVirtual()) {
                $page = $this->convertPage($page, $this->getOption('frontmatter.format'));
                $this->pageCollection->replace($page->getId(), $page);
            }
        }
    }

    /**
     * Converts page content:
     * * Yaml frontmatter to PHP array
     * * Mardown body to HTML.
     *
     * @param Page   $page
     * @param string $format
     *
     * @return Page
     */
    public function convertPage($page, $format = 'yaml')
    {
        // converts frontmatter
        $variables = (new Converter())
            ->convertFrontmatter($page->getFrontmatter(), $format);
        // converts body
        $html = (new Converter())
            ->convertBody($page->getBody());
        /*
         * Setting default page properties
         */
        if (!empty($variables['title'])) {
            $page->setTitle($variables['title']);
            unset($variables['title']);
        }
        if (!empty($variables['section'])) {
            $page->setSection($variables['section']);
            unset($variables['section']);
        }
        if (!empty($variables['date'])) {
            $page->setDate($variables['date']);
            //unset($variables['date']);
        }
        if (!empty($variables['permalink'])) {
            $page->setPermalink($variables['permalink']);
            unset($variables['permalink']);
        }
        if (!empty($variables['layout'])) {
            $page->setLayout($variables['layout']);
            unset($variables['layout']);
        }
        $page->setHtml($html);
        // setting page variables
        $page->setVariables($variables);

        return $page;
    }

    /**
     * Generates sections.
     *
     * @see addNodePage()
     * @see build()
     */
    protected function generateSections()
    {
        // collects sections
        /* @var $page Page */
        foreach ($this->pageCollection as $page) {
            if ($page->getSection() != '') {
                $this->sections[$page->getSection()][] = $page;
            }
        }
        // adds node pages
        if (count($this->sections) > 0) {
            $menu = 100;

            foreach ($this->sections as $node => $pages) {
                if (!$this->pageCollection->has($node)) {
                    usort($pages, 'PHPoole\Page\Utils::sortByDate');
                    $this->addNodePage(NodeTypeEnum::SECTION, $node, $node, $pages, [], $menu);
                }
                $menu += 10;
            }
        }
    }

    /**
     * Generates taxonomies.
     *
     * @see addNodePage()
     * @see build()
     */
    protected function generateTaxonomies()
    {
        if (array_key_exists('taxonomies', $this->getOption('site'))) {
            // collects taxonomies from pages
            $this->taxonomies = new Taxonomy\Collection();
            $siteTaxonomies = $this->getOption('site.taxonomies');
            // adds each vocabulary collection to the taxonomies collection
            foreach ($siteTaxonomies as $plural => $singular) {
                $this->taxonomies->add(new Taxonomy\Vocabulary($plural));
            }
            /* @var $page Page */
            foreach ($this->pageCollection as $page) {
                foreach ($siteTaxonomies as $plural => $singular) {
                    if (isset($page[$plural])) {
                        // converts a list to an array if necessary
                        if (!is_array($page[$plural])) {
                            $page->setVariable($plural, [$page[$plural]]);
                        }
                        foreach ($page[$plural] as $term) {
                            // adds each terms to the vocabulary collection
                            $this->taxonomies->get($plural)
                                ->add(new Taxonomy\Term($term));
                            // adds each pages to the term collection
                            $this->taxonomies
                                ->get($plural)
                                ->get($term)
                                ->add($page);
                        }
                    }
                }
            }
            // adds node pages
            /* @var $terms \PHPoole\Taxonomy\Vocabulary */
            foreach ($this->taxonomies as $plural => $terms) {
                if (count($terms) > 0) {
                    /*
                     * Creates $plural/$term pages (list of pages)
                     * ex: /tags/tag-1/
                     */
                    /* @var $pages PageCollection */
                    foreach ($terms as $term => $pages) {
                        if (!$this->pageCollection->has($term)) {
                            /* @var $pages PageCollection */
                            $this->addNodePage(NodeTypeEnum::TAXONOMY,
                                $term,
                                sprintf('%s/%s', $plural, $term),
                                $pages->sortByDate()->toArray(),
                                ['singular' => $siteTaxonomies[$plural]]
                            );
                        }
                    }
                    /*
                     * Creates $plural pages (list of terms)
                     * ex: /tags/
                     */
                    $page = (new Page())
                        ->setId(strtolower($plural))
                        ->setPathname(strtolower($plural))
                        ->setTitle($plural)
                        ->setNodeType(NodeTypeEnum::TERMS)
                        ->setVariable('plural', $plural)
                        ->setVariable('singular', $siteTaxonomies[$plural])
                        ->setVariable('terms', $terms);

                    // add page only if a template exist
                    try {
                        $this->layoutFinder($page);
                        $this->pageCollection->add($page);
                    } catch (\Exception $e) {
                        echo $e->getMessage()."\n";
                        // do not add page
                        unset($page);
                    }
                }
            }
        }
    }

    /**
     * Generates homepage.
     *
     * @see addNodePage()
     * @see build()
     */
    protected function generateHomepage()
    {
        if (!$this->pageCollection->has('index')) {
            $filteredPages = $this->pageCollection->filter(function (Page $page) {
                /* @var $page Page */
                return $page->getNodeType() === null && $page->getSection() == $this->getOption('paginate.homepage.section');
            });

            $pages = $filteredPages->sortByDate()->toArray();

            $this->addNodePage(NodeTypeEnum::HOMEPAGE, 'Home', '', $pages, [], 1);
        }
    }

    /**
     * Generates aliases.
     *
     * @see build()
     */
    protected function generatesAliases()
    {
        /* @var $page Page */
        foreach ($this->pageCollection as $page) {
            if ($page->hasVariable('aliases')) {
                $aliases = $page->getVariable('aliases');
                foreach ($aliases as $alias) {
                    /* @var $redirectPage Page */
                    $aliasPage = new Page();
                    $aliasPage->setId($alias)
                        ->setPathname(Page::urlize($alias))
                        ->setTitle($alias)
                        ->setLayout('redirect')
                        ->setVariable('destination', $page->getPermalink());
                    $this->pageCollection->add($aliasPage);
                }
            }
        }
    }

    /**
     * Adds a node page.
     *
     * A node page is a virtual page created from/with
     * the list of children pages.
     *
     * @param string $type       Node type, see NodeTypeEnum
     * @param string $title      Page title
     * @param string $path       Page path
     * @param array  $pages      Pages collection as array
     * @param array  $variables  Page variables
     * @param int    $menuWeight Weight of the menu entry
     */
    protected function addNodePage(
        $type,
        $title,
        $path,
        array $pages,
        array $variables = [],
        $menuWeight = 0
    ) {
        $paginate = $this->getOption('site.paginate');
        $disabled = ($paginate == 'disabled') ? true : false;
        $paginateMax = $paginate['max'];
        $paginatePath = $paginate['path'];
        // paginate
        if (!$disabled && (isset($paginateMax) && count($pages) > $paginateMax)) {
            $paginateCount = ceil(count($pages) / $paginateMax);
            for ($i = 0; $i < $paginateCount; $i++) {
                $pagesInPagination = array_slice($pages, ($i * $paginateMax), ($i * $paginateMax) + $paginateMax);
                // first
                if ($i == 0) {
                    $page = (new Page())
                        ->setId(Page::urlize(sprintf('%s/index', $path)))
                        ->setPathname(Page::urlize(sprintf('%s', $path)))
                        ->setVariable('aliases', [
                            sprintf('%s/%s/%s', $path, $paginatePath, 1),
                        ]);
                    if ($menuWeight) {
                        $page->setVariable('menu', [
                            'main' => ['weight' => $menuWeight],
                        ]);
                    }
                // others
                } else {
                    $page = (new Page())
                        ->setId(Page::urlize(sprintf('%s/%s/%s/index', $path, $paginatePath, $i + 1)))
                        ->setPathname(Page::urlize(sprintf('%s/%s/%s', $path, $paginatePath, $i + 1)));
                }
                // pagination
                $pagination = ['pages' => $pagesInPagination];
                if ($i > 0) {
                    $pagination += ['prev' => Page::urlize(sprintf('%s/%s/%s', $path, $paginatePath, $i))];
                }
                if ($i < $paginateCount - 1) {
                    $pagination += ['next' => Page::urlize(sprintf('%s/%s/%s', $path, $paginatePath, $i + 2))];
                }
                // common properties/variables
                $page->setTitle(ucfirst($title))
                    ->setNodeType($type)
                    ->setVariable('pages', $pages)
                    ->setVariable('pagination', $pagination);
                if (!empty($variables)) {
                    foreach ($variables as $key => $value) {
                        $page->setVariable($key, $value);
                    }
                }
                $this->pageCollection->add($page);
            }
        // not paginate
        } else {
            $page = (new Page())
                ->setId(Page::urlize(sprintf('%s/index', $path)))
                ->setPathname(Page::urlize(sprintf('%s', $path)))
                ->setTitle(ucfirst($title))
                ->setNodeType($type)
                ->setVariable('pages', $pages)
                ->setVariable('pagination', ['pages' => $pages]);
            if ($menuWeight) {
                $page->setVariable('menu', [
                    'main' => ['weight' => $menuWeight],
                ]);
            }
            if (!empty($variables)) {
                foreach ($variables as $key => $value) {
                    $page->setVariable($key, $value);
                }
            }
            $this->pageCollection->add($page);
        }
    }

    /**
     * Generates menus.
     *
     * @see build()
     */
    protected function generateMenus()
    {
        $this->menus = new Menu\Collection();

        /* @var $page Page */
        foreach ($this->pageCollection as $page) {
            if (!empty($page['menu'])) {
                // single
                /*
                 * ex:
                 * menu: main
                 */
                if (is_string($page['menu'])) {
                    $item = (new Menu\Entry($page->getId()))
                        ->setName($page->getTitle())
                        ->setUrl($page->getPermalink());
                    /* @var $menu Menu\Menu */
                    $menu = $this->menus->get($page['menu']);
                    $menu->add($item);
                }
                // multiple
                /*
                 * ex:
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
        /*
         * Removing/adding/replacing menus entries from options array
         */
        if ($this->getOption('site.menu') !== '') {
            foreach ($this->getOption('site.menu') as $name => $entry) {
                /* @var $menu Menu\Menu */
                $menu = $this->menus->get($name);
                foreach ($entry as $property) {
                    if (isset($property['disabled']) && $property['disabled']) {
                        if (isset($property['id']) && $menu->has($property['id'])) {
                            $menu->remove($property['id']);
                        }
                        continue;
                    }
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
        // prepares global site variables
        $this->site = array_merge(
            $this->getOption('site'),
            ['menus' => $this->menus],
            ['pages' => $this->pageCollection]
        );
        // prepares renderer
        if (!is_dir($this->sourceDir.'/'.$this->getOption('layouts.dir'))) {
            throw new \Exception(sprintf("'%s' is not a valid layouts directory", $this->getOption('layouts.dir')));
        } else {
            $paths[] = $this->sourceDir.'/'.$this->getOption('layouts.dir');
        }
        if ($this->isTheme()) {
            $paths[] = $this->sourceDir.'/'.$this->getOption('themes.dir').'/'.$this->theme.'/layouts';
        }
        $this->renderer = new Renderer\Twig($paths);
        // adds global variables
        $this->renderer->addGlobal('site', $this->site);
        $this->renderer->addGlobal('phpoole', [
            'url'       => 'http://narno.org/PHPoole-library/#v'.self::getVersion(),
            'version'   => self::getVersion(),
            'poweredby' => 'PHPoole v'.self::getVersion(),
        ]);

        // start rendering
        $dir = $this->destDir.'/'.$this->getOption('output.dir');
        $this->fs->mkdir($dir);
        /* @var $page Page */
        foreach ($this->pageCollection as $page) {
            $this->renderPage($page, $dir);
        }
    }

    /**
     * Render a page.
     *
     * @param Page   $page
     * @param string $dir
     *
     * @throws \Exception
     *
     * @see renderPages()
     */
    protected function renderPage(Page $page, $dir)
    {
        $this->renderer->render($this->layoutFinder($page), [
            'page' => $page,
        ]);

        // force pathname of a none virtual node page
        if ($page->getName() == 'index') {
            $pathname = $dir.'/'.$page->getPath().'/'.$this->getOption('output.filename');
        // pathname of a page
        } else {
            if (empty(pathinfo($page->getPermalink(), PATHINFO_EXTENSION))) {
                $pathname = $dir.'/'.$page->getPermalink().'/'.$this->getOption('output.filename');
            } else {
                $pathname = $dir.'/'.$page->getPermalink();
            }
        }

        $pathname = preg_replace('#/+#', '/', $pathname); // remove unnecessary slashes
        $this->renderer->save($pathname);
        echo $pathname."\n";
    }

    /**
     * Copy static directory content to site root.
     *
     * @see build()
     */
    protected function copyStatic()
    {
        $dir = $this->destDir.'/'.$this->getOption('output.dir');
        // copy theme static dir if exists
        if ($this->isTheme()) {
            $themeStaticDir = $this->sourceDir.'/'.$this->getOption('themes.dir').'/'.$this->theme.'/static';
            if ($this->fs->exists($themeStaticDir)) {
                $this->fs->mirror($themeStaticDir, $dir, null, ['override' => true]);
            }
        }
        // copy static dir if exists
        $staticDir = $this->sourceDir.'/'.$this->getOption('static.dir');
        if ($this->fs->exists($staticDir)) {
            $this->fs->mirror($staticDir, $dir, null, ['override' => true]);
        }
    }

    /**
     * Uses a theme?
     * If yes, set $theme variable.
     *
     * @throws \Exception
     *
     * @return bool
     */
    protected function isTheme()
    {
        if ($this->theme !== null) {
            return true;
        }
        if ($this->getOption('theme') !== '') {
            $themesDir = $this->sourceDir.'/'.$this->getOption('themes.dir');
            if ($this->fs->exists($themesDir.'/'.$this->getOption('theme'))) {
                $this->theme = $this->getOption('theme');

                return true;
            }
            throw new \Exception(sprintf("Theme directory '%s' not found!", $themesDir));
        }

        return false;
    }

    /**
     * Layout file finder.
     *
     * @param Page $page
     *
     * @throws \Exception
     *
     * @return string
     */
    protected function layoutFinder(Page $page)
    {
        $layout = 'unknown';

        if ($page->getLayout() == 'redirect') {
            return $page->getLayout().'.html';
        }

        $layouts = $this->layoutFallback($page);

        // is layout exists in local layout dir?
        $layoutsDir = $this->sourceDir.'/'.$this->getOption('layouts.dir');
        foreach ($layouts as $layout) {
            if ($this->fs->exists($layoutsDir.'/'.$layout)) {
                return $layout;
            }
        }
        // is layout exists in layout theme dir?
        if ($this->isTheme()) {
            $themeDir = $this->sourceDir.'/'.$this->getOption('themes.dir').'/'.$this->theme.'/layouts';
            foreach ($layouts as $layout) {
                if ($this->fs->exists($themeDir.'/'.$layout)) {
                    return $layout;
                }
            }
        }
        throw new \Exception(sprintf("Layout '%s' not found for page '%s'!", $layout, $page->getId()));
    }

    /**
     * Layout fall-back.
     *
     * @param $page
     *
     * @return string[]
     *
     * @see layoutFinder()
     */
    protected function layoutFallback(Page $page)
    {
        switch ($page->getNodeType()) {
            case NodeTypeEnum::HOMEPAGE:
                $layouts = [
                    'index.html',
                    '_default/list.html',
                    '_default/page.html',
                ];
                break;
            case NodeTypeEnum::SECTION:
                $layouts = [
                    // 'section/$section.html'
                    '_default/section.html',
                    '_default/list.html',
                ];
                if ($page->getSection() !== null) {
                    $layouts = array_merge([sprintf('section/%s.html', $page->getSection())], $layouts);
                }
                break;
            case NodeTypeEnum::TAXONOMY:
                $layouts = [
                    // 'taxonomy/$singular.html'
                    '_default/taxonomy.html',
                    '_default/list.html',
                ];
                if ($page->getVariable('singular') !== null) {
                    $layouts = array_merge([sprintf('taxonomy/%s.html', $page->getVariable('singular'))], $layouts);
                }
                break;
            case NodeTypeEnum::TERMS:
                $layouts = [
                    // 'taxonomy/$singular.terms.html'
                    '_default/terms.html',
                ];
                if ($page->getVariable('singular') !== null) {
                    $layouts = array_merge([sprintf('taxonomy/%s.terms.html', $page->getVariable('singular'))], $layouts);
                }
                break;
            default:
                $layouts = [
                    // '$section/page.html'
                    // '$section/$layout.html'
                    // '$layout.html'
                    // 'page.html'
                    '_default/page.html',
                ];
                if ($page->getSection() !== null) {
                    $layouts = array_merge([sprintf('%s/page.html', $page->getSection())], $layouts);
                    if ($page->getLayout() !== null) {
                        $layouts = array_merge([sprintf('%s/%s.html', $page->getSection(), $page->getLayout())], $layouts);
                    }
                } else {
                    $layouts = array_merge(['page.html'], $layouts);
                    if ($page->getLayout() !== null) {
                        $layouts = array_merge([sprintf('%s.html', $page->getLayout())], $layouts);
                    }
                }
        }

        return $layouts;
    }

    /**
     * Return version.
     *
     * @return string
     */
    protected static function getVersion()
    {
        $version = self::VERSION;

        if (file_exists(__DIR__.'/../composer.json')) {
            $composer = json_decode(file_get_contents(__DIR__.'/../composer.json'), true);
            if (isset($composer['version'])) {
                $version = $composer['version'];
            }
        }

        return $version;
    }
}
