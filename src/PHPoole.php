<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole;

use PHPoole\Page\Collection as PageCollection;
use PHPoole\Page\Converter;
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

    const VERSION = '1.0.x-dev';
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
     * Constructor.
     *
     * @param null  $sourceDir
     * @param null  $destDir
     * @param array $options
     *
     * @throws \Exception
     */
    public function __construct($sourceDir = null, $destDir = null, $options = [])
    {
        if ($sourceDir === null) {
            $sourceDir = getcwd();
        }
        if (!is_dir($sourceDir)) {
            throw new \Exception(sprintf("'%s' is not a valid source directory.", $sourceDir));
        }
        if ($destDir === null) {
            $destDir = $sourceDir;
        }
        if (!is_dir($destDir)) {
            throw new \Exception(sprintf("'%s' is not a valid destination directory.", $destDir));
        }
        $this->sourceDir = $sourceDir;
        $this->destDir = $destDir;

        $options = array_replace_recursive([
            'site' => [
                'title'       => 'PHPoole', // site title
                'baseline'    => 'A PHPoole website', // site baseline
                'baseurl'     => 'http://localhost:8000/', // php -S localhost:8000 -t _site/ >/dev/null
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', // site description
                'taxonomies'  => [ // list of taxonomies
                    'tags'       => 'tag',      // tag vocabulary
                    'categories' => 'category', // category vocabulary
                ],
                'paginate' => [ // pagination options
                    'max'  => 5,      // maximum numbers of listed pages
                    'path' => 'page', // ie: section/page/2
                ],
            ],
            'content' => [
                'dir' => 'content', // content directory (from source)
                'ext' => 'md',      // file extension (*.md)
            ],
            'frontmatter' => [
                'format' => 'yaml', // yaml or ini
            ],
            'body' => [
                'format' => 'md', // body format, Markdown by default
            ],
            'static' => [
                'dir' => 'static', // static files directory
            ],
            'layouts' => [
                'dir' => 'layouts', // layouts/templates files directory
            ],
            'output' => [
                'dir'      => '_site',      // output directory
                'filename' => 'index.html', // default filename of generated files
            ],
            'themes' => [
                'dir' => 'themes', // themes directory
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
     * Builds a new website.
     */
    public function build()
    {
        // locates content, creates Pages collection and parses pages content
        $this->locateContent();
        $this->addPagesFromContent();
        $this->convertPages();
        // generates sections and taxonomies
        $this->generateSections();
        $this->generateTaxonomies();
        // adds pages from generators to Pages collection
        $this->addSectionPages();
        $this->addTaxonomyPages();
        $this->addTaxonomyTermsPages();
        $this->addHomePage();
        $this->addRedirectPages();
        // generates menus
        $this->generateMenus();
        // rendering
        $this->addSiteVars();
        $this->renderPages();
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
            $dir = $this->sourceDir.'/'.$this->getOptions()['content']['dir'];
            $params = compact('dir');
            $this->triggerPre(__FUNCTION__, $params);
            $this->contentIterator = Finder::create()
                ->files()
                ->in($params['dir'])
                ->name('*.'.$this->getOptions()['content']['ext']);
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
     * Adds pages to Pages collection from content iterator.
     *
     * @see build()
     */
    protected function addPagesFromContent()
    {
        $this->pageCollection = new PageCollection();
        if (count($this->contentIterator) <= 0) {
            //throw new \Exception('No content files found.');
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
            //throw new \Exception('No pages found.');
            return;
        }
        /* @var $page Page */
        foreach ($this->pageCollection as $page) {
            if (!$page->isVirtual()) {
                $page = $this->convertPage($page, $this->getOptions()['frontmatter']['format']);
                $this->pageCollection->replace($page->getId(), $page);
            }
        }
    }

    /**
     * Converts page content:
     * * Yaml frontmatter to PHP array
     * * Mardown body to HTML
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
            unset($variables['date']);
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
     * @see build()
     */
    protected function generateSections()
    {
        /* @var $page Page */
        foreach ($this->pageCollection as $page) {
            if ($page->getSection() != '') {
                $this->sections[$page->getSection()][] = $page;
            }
        }
    }

    /**
     * Generates taxonomies.
     *
     * @see build()
     */
    protected function generateTaxonomies()
    {
        /*
         * Builds collections
         */
        if (array_key_exists('taxonomies', $this->getOptions()['site'])) {
            $this->taxonomies = new Taxonomy\Collection();
            $siteTaxonomies = $this->getOptions()['site']['taxonomies'];
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
        }
    }

    /**
     * Adds a node page.
     *
     * A node page is a virtual page created from/with
     * the list of children pages.
     *
     * @param string $type      Node type, see NodeTypeEnum
     * @param string $title     Page title
     * @param string $path      Page path
     * @param array  $pages     Pages collection as array
     * @param array  $variables Page variables
     * @param int    $menu      Weight of the menu entry
     */
    protected function addNodePage(
        $type,
        $title,
        $path,
        array $pages,
        array $variables = [],
        $menu = 0
    ) {
        $paginate = $this->getOptions()['site']['paginate'];
        $disabled = (!array_key_exists('paginate', $paginate) || $paginate == 'disabled') ? true : false;
        $paginateMax = $paginate['max'];
        $paginatePath = $paginate['path'];
        // paginate
        if (!$disabled && (isset($paginateMax) && count($pages) > $paginateMax)) {
            $paginateCount = ceil(count($pages) / $paginateMax);
            for ($i = 0; $i < $paginateCount; $i++) {
                $pagesInPaginator = array_slice($pages, ($i * $paginateMax), ($i * $paginateMax) + $paginateMax);
                // first
                if ($i == 0) {
                    $page = (new Page())
                        ->setId(Page::urlize(sprintf('%s/index', $path)))
                        ->setPathname(Page::urlize(sprintf('%s', $path)))
                        ->setVariable('aliases', [
                            sprintf('%s/%s/%s', $path, $paginatePath, 1),
                        ]);
                    if ($menu) {
                        $page->setVariable('menu', [
                            'main' => ['weight' => $menu],
                        ]);
                    }
                // others
                } else {
                    $page = (new Page())
                        ->setId(Page::urlize(sprintf('%s/%s/%s/index', $path, $paginatePath, $i + 1)))
                        ->setPathname(Page::urlize(sprintf('%s/%s/%s', $path, $paginatePath, $i + 1)));
                }
                // paginator
                $paginator = [];
                if ($i > 0) {
                    $paginator += ['prev'  => Page::urlize(sprintf('%s/%s/%s', $path, $paginatePath, $i))];
                }
                if ($i < $paginateCount - 1) {
                    $paginator += ['next'  => Page::urlize(sprintf('%s/%s/%s', $path, $paginatePath, $i + 2))];
                }
                // common properties/variables
                $page->setTitle(ucfirst($title))
                    ->setNodeType($type)
                    ->setVariable('pages', $pagesInPaginator)
                    ->setVariable('paginator', $paginator);
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
                ->setVariable('pages', $pages);
            if ($menu) {
                $page->setVariable('menu', [
                    'main' => ['weight' => $menu],
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
     * Adds section pages to collection.
     *
     * @see build()
     */
    protected function addSectionPages()
    {
        if (count($this->sections) > 0) {
            $menu = 100;
            foreach ($this->sections as $node => $pages) {
                if (!$this->pageCollection->has($node)) {
                    $this->addNodePage(NodeTypeEnum::SECTION, $node, $node, $pages, [], $menu);
                }
                $menu += 10;
            }
        }
    }

    /**
     * Adds taxonomy pages.
     *
     * @see build()
     */
    protected function addTaxonomyPages()
    {
        $siteTaxonomies = $this->getOptions()['site']['taxonomies'];
        foreach ($this->taxonomies as $plural => $terms) {
            /*
             * Create $plural/$term pages (list of pages)
             * ex: /tags/tag-1/
             */
            if (count($terms) > 0) {
                foreach ($terms as $node => $pages) {
                    if (!$this->pageCollection->has($node)) {
                        /* @var $pages Collection\CollectionInterface */
                        $this->addNodePage(NodeTypeEnum::TAXONOMY, $node, "$plural/$node", $pages->toArray(), ['singular' => $siteTaxonomies[$plural]]);
                    }
                }
            }
        }
    }

    /**
     * Adds taxonomy terms pages.
     *
     * @see build()
     */
    protected function addTaxonomyTermsPages()
    {
        $siteTaxonomies = $this->getOptions()['site']['taxonomies'];
        foreach ($this->taxonomies as $plural => $terms) {
            if (count($terms) > 0) {
                /*
                 * Create $plural pages (list of terms)
                 * ex: /tags/
                 */
                $page = (new Page())
                    ->setId(strtolower($plural))
                    ->setPathname(strtolower($plural))
                    ->setTitle($plural)
                    ->setNodeType('terms')
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

    /**
     * Adds homepage to collection.
     *
     * @see build()
     */
    protected function addHomePage()
    {
        if (!$this->pageCollection->has('index')) {
            $filtered = $this->pageCollection->filter(function (Page $item) {
                /* @var $item Page */
                return $item->getNodeType() === null;
            });
            $this->addNodePage(NodeTypeEnum::HOMEPAGE, 'Home', '', $filtered->toArray(), [], 1);
        }
    }

    /**
     * Adds redirect pages.
     *
     * @see build()
     */
    protected function addRedirectPages()
    {
        /* @var $page Page */
        foreach ($this->pageCollection as $page) {
            if ($page->hasVariable('aliases')) {
                $redirects = $page->getVariable('aliases');
                foreach ($redirects as $redirect) {
                    /* @var $redirectPage Page */
                    $redirectPage = new Page();
                    $redirectPage->setId($redirect)
                        ->setPathname(Page::urlize($redirect))
                        ->setTitle($redirect)
                        ->setLayout('redirect')
                        ->setVariable('destination', $page->getPermalink());
                    $this->pageCollection->add($redirectPage);
                }
            }
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
        // @todo use collection filter?
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
        if (isset($this->getOptions()['site']['menu'])) {
            foreach ($this->getOptions()['site']['menu'] as $name => $value) {
                /* @var $menu Menu\Menu */
                $menu = $this->menus->get($name);
                if (isset($value['disabled']) && $value['disabled']) {
                    if (isset($value['id']) && $menu->has($value['id'])) {
                        $menu->remove($value['id']);
                    }
                    continue;
                }
                $item = (new Menu\Entry($value['id']))
                    ->setName($value['name'])
                    ->setUrl($value['url'])
                    ->setWeight($value['weight']);
                $menu->add($item);
            }
        }
    }

    /**
     * Adds site variables.
     *
     * @see build()
     */
    protected function addSiteVars()
    {
        $this->site = array_merge(
            $this->getOptions()['site'],
            ['menus' => $this->menus],
            ['pages' => $this->pageCollection]
        );
    }

    /**
     * Pages rendering:
     * 1. Iterates Pages collection
     * 2. Applies Twig templates
     * 3. Saves rendered files
     *
     * @see renderPage()
     * @see build()
     */
    protected function renderPages()
    {
        // prepare renderer
        if (!is_dir($this->sourceDir.'/'.$this->getOptions()['layouts']['dir'])) {
            throw new \Exception(sprintf("'%s' is not a valid layouts directory", $this->getOptions()['layouts']['dir']));
        }
        $this->renderer = new Renderer\Twig($this->sourceDir.'/'.$this->getOptions()['layouts']['dir']);
        // add theme templates
        if ($this->isTheme()) {
            $this->renderer->addPath($this->sourceDir.'/'.$this->getOptions()['themes']['dir'].'/'.$this->theme.'/layouts');
        }
        // add global variables
        $this->renderer->addGlobal('site', $this->site);
        $this->renderer->addGlobal('phpoole', [
            'url'       => 'http://phpoole.narno.org/#v2',
            'version'   => self::VERSION,
            'poweredby' => 'PHPoole v'.self::VERSION,
        ]);

        // start rendering
        $dir = $this->destDir.'/'.$this->getOptions()['output']['dir'];
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
        //echo '- page layout: '.$page->getLayout() . "\n";
        //echo '- used layout: '.$this->layoutFinder($page) . "\n";
        $this->renderer->render($this->layoutFinder($page), [
            'page' => $page,
        ]);

        // force pathname of a none virtual node page
        if ($page->getName() == 'index') {
            $pathname = $dir.'/'.$page->getPath().'/'.$this->getOptions()['output']['filename'];
        // pathname of a page
        } else {
            if (empty(pathinfo($page->getPermalink(), PATHINFO_EXTENSION))) {
                $pathname = $dir.'/'.$page->getPermalink().'/'.$this->getOptions()['output']['filename'];
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
        $dir = $this->destDir.'/'.$this->getOptions()['output']['dir'];
        // copy theme static dir if exists
        if ($this->isTheme()) {
            $themeStaticDir = $this->sourceDir.'/'.$this->getOptions()['themes']['dir'].'/'.$this->theme.'/static';
            if ($this->fs->exists($themeStaticDir)) {
                $this->fs->mirror($themeStaticDir, $dir, null, ['override' => true]);
            }
        }
        // copy static dir if exists
        $staticDir = $this->sourceDir.'/'.$this->getOptions()['static']['dir'];
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
        if (isset($this->getOptions()['theme'])) {
            $themesDir = $this->sourceDir.'/'.$this->getOptions()['themes']['dir'];
            if ($this->fs->exists($themesDir.'/'.$this->getOptions()['theme'])) {
                $this->theme = $this->getOptions()['theme'];

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
        $layoutsDir = $this->sourceDir.'/'.$this->getOptions()['layouts']['dir'];
        foreach ($layouts as $layout) {
            if ($this->fs->exists($layoutsDir.'/'.$layout)) {
                return $layout;
            }
        }
        // is layout exists in layout theme dir?
        if ($this->isTheme()) {
            $themeDir = $this->sourceDir.'/'.$this->getOptions()['themes']['dir'].'/'.$this->theme.'/layouts';
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
                    if ($page->getLayout() != null) {
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
}
