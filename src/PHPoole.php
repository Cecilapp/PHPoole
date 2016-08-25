<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole;

use PHPoole\Converter\Converter;
use PHPoole\Generator\GeneratorManager;
use PHPoole\Page\Collection as PageCollection;
use PHPoole\Page\NodeType;
use PHPoole\Page\Page;
use Symfony\Component\Filesystem\Filesystem;
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
     * Options.
     *
     * @var Options
     */
    protected $options;
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
     * @var Renderer\RendererInterface
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
     * @param Options|array|null $options
     * @param \Closure|null      $messageCallback
     */
    public function __construct($options = null, \Closure $messageCallback = null)
    {
        $this->setOptions($options);
        $this->options->setSourceDir(null)->setDestinationDir(null);
        $this->setMessageCallback($messageCallback);
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
     * @param Options|array|null $options
     *
     * @return $this
     */
    public function setOptions($options)
    {
        if (!$options instanceof Options) {
            $options = new Options($options);
        }
        if ($this->options !== $options) {
            $this->options = $options;
        }

        return $this;
    }

    /**
     * @return Options
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Options::setSourceDir alias.
     *
     * @param $sourceDir
     *
     * @return $this
     */
    public function setSourceDir($sourceDir)
    {
        $this->options->setSourceDir($sourceDir);

        return $this;
    }

    /**
     * Options::setDestinationDir alias.
     *
     * @param $destinationDir
     *
     * @return $this
     */
    public function setDestinationDir($destinationDir)
    {
        $this->options->setDestinationDir($destinationDir);

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
                ->in($this->options->getContentPath())
                ->name('*.'.$this->options->get('content.ext'));
            if (!$this->content instanceof Finder) {
                throw new \Exception(__FUNCTION__.': result must be an instance of Symfony\Component\Finder.');
            }
        } catch (\Exception $e) {
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
                if (false !== $convertedPage = $this->convertPage($page, $this->options->get('frontmatter.format'))) {
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
        } catch (\Exception $e) {
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
        $generators = $this->options->get('generators');
        $this->generatorManager = new GeneratorManager();
        array_walk($generators, function ($generator, $priority) {
            $generator = sprintf('\\PHPoole\\Generator\\%s', $generator);
            if (!class_exists($generator)) {
                $message = sprintf("> Unable to load generator '%s'", $generator);
                call_user_func_array($this->messageCallback, ['GENERATE_PROGRESS', $message]);

                return;
            }
            $this->generatorManager->addGenerator(new $generator($this->options), $priority);
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
         * Removing/adding/replacing menus entries from options array
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
        if (!empty($this->options->get('site.menu'))) {
            foreach ($this->options->get('site.menu') as $name => $entry) {
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
        if ($this->isTheme()) {
            $themeStaticDir = $this->options->getThemePath($this->theme, 'static');
            if ($this->fs->exists($themeStaticDir)) {
                $this->fs->mirror($themeStaticDir, $this->options->getOutputPath(), null, ['override' => true]);
            }
        }
        // copy static dir if exists
        $staticDir = $this->options->getStaticPath();
        if ($this->fs->exists($staticDir)) {
            $finder = new Finder();
            $finder->files()->filter(function (\SplFileInfo $file) {
                return !(is_array($this->options->get('static.exclude'))
                    && in_array($file->getBasename(), $this->options->get('static.exclude')));
            })->in($staticDir);
            $this->fs->mirror($staticDir, $this->options->getOutputPath(), $finder, ['override' => true]);
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
        // prepares global site variables
        $site = array_merge(
            $this->options->get('site'),
            ['menus' => $this->menus],
            ['pages' => $this->pages]
        );
        // prepares renderer
        if (!is_dir($this->options->getLayoutsPath())) {
            throw new \Exception(sprintf("'%s' is not a valid layouts directory", $this->options->getLayoutsPath()));
        } else {
            $paths[] = $this->options->getLayoutsPath();
        }
        if ($this->isTheme()) {
            $paths[] = $this->options->getThemePath($this->theme);
        }
        $this->renderer = new Renderer\Twig($paths, $this->options);
        // adds global variables
        $this->renderer->addGlobal('site', $site);
        $this->renderer->addGlobal('phpoole', [
            'url'       => 'http://phpoole.org/#v'.$this->getVersion(),
            'version'   => $this->getVersion(),
            'poweredby' => 'PHPoole-library v'.self::getVersion(),
        ]);

        // start rendering
        $this->fs->mkdir($this->options->getOutputPath());
        call_user_func_array($this->messageCallback, ['RENDER', 'Rendering pages']);
        $max = count($this->pages);
        $count = 0;
        /* @var $page Page */
        foreach ($this->pages as $page) {
            $count++;
            $pathname = $this->renderPage($page, $this->options->getOutputPath());
            $message = substr($pathname, strlen($this->options->getDestinationDir()) + 1);
            call_user_func_array($this->messageCallback, ['RENDER_PROGRESS', $message, $count, $max]);
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
     *
     * @return string Path to the generated page
     */
    protected function renderPage(Page $page, $dir)
    {
        $this->renderer->render($this->layoutFinder($page), ['page' => $page]);

        // force pathname of a (non virtual) node page
        if ($page->getName() == 'index') {
            $pathname = $dir.'/'.$page->getPath().'/'.$this->options->get('output.filename');
        // pathname of a (normal) page
        } else {
            if (empty(pathinfo($page->getPermalink(), PATHINFO_EXTENSION))) {
                $pathname = $dir.'/'.$page->getPermalink().'/'.$this->options->get('output.filename');
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
        if ($this->options->get('theme') !== '') {
            $themesDir = $this->options->getThemesPath();
            if ($this->fs->exists($themesDir.'/'.$this->options->get('theme'))) {
                $this->theme = $this->options->get('theme');

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

        if ($page->getLayout() == 'redirect.html') {
            return $page->getLayout().'.twig';
        }

        $layouts = $this->layoutFallback($page);

        // is layout exists in local layout dir?
        $layoutsDir = $this->options->getLayoutsPath();
        foreach ($layouts as $layout) {
            if ($this->fs->exists($layoutsDir.'/'.$layout)) {
                return $layout;
            }
        }
        // is layout exists in layout theme dir?
        if ($this->isTheme()) {
            $themeDir = $this->options->getThemePath($this->theme);
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
        // remove redundant '.twig' extension
        $layout = str_replace('.twig', '', $page->getLayout());

        switch ($page->getNodeType()) {
            case NodeType::HOMEPAGE:
                $layouts = [
                    'index.html.twig',
                    '_default/list.html.twig',
                    '_default/page.html.twig',
                ];
                break;
            case NodeType::SECTION:
                $layouts = [
                    // 'section/$section.html.twig',
                    '_default/section.html.twig',
                    '_default/list.html.twig',
                ];
                if ($page->getSection() !== null) {
                    $layouts = array_merge([sprintf('section/%s.html.twig', $page->getSection())], $layouts);
                }
                break;
            case NodeType::TAXONOMY:
                $layouts = [
                    // 'taxonomy/$singular.html.twig',
                    '_default/taxonomy.html.twig',
                    '_default/list.html.twig',
                ];
                if ($page->getVariable('singular') !== null) {
                    $layouts = array_merge([sprintf('taxonomy/%s.html.twig', $page->getVariable('singular'))], $layouts);
                }
                break;
            case NodeType::TERMS:
                $layouts = [
                    // 'taxonomy/$singular.terms.html.twig',
                    '_default/terms.html.twig',
                ];
                if ($page->getVariable('singular') !== null) {
                    $layouts = array_merge([sprintf('taxonomy/%s.terms.html.twig', $page->getVariable('singular'))], $layouts);
                }
                break;
            default:
                $layouts = [
                    // '$section/page.html.twig',
                    // '$section/$layout.twig',
                    // '$layout.twig',
                    // 'page.html.twig',
                    '_default/page.html.twig',
                ];
                if ($page->getSection() !== null) {
                    $layouts = array_merge([sprintf('%s/page.html.twig', $page->getSection())], $layouts);
                    if ($page->getLayout() !== null) {
                        $layouts = array_merge([sprintf('%s/%s.twig', $page->getSection(), $layout)], $layouts);
                    }
                } else {
                    $layouts = array_merge(['page.html.twig'], $layouts);
                    if ($page->getLayout() !== null) {
                        $layouts = array_merge([sprintf('%s.twig', $layout)], $layouts);
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
