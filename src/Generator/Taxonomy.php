<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Generator;

use Dflydev\DotAccessData\Data;
use PHPoole\Page\Collection as PageCollection;
use PHPoole\Page\NodeType;
use PHPoole\Page\Page;
use PHPoole\Taxonomy\Collection as TaxonomyCollection;
use PHPoole\Taxonomy\Term as Term;
use PHPoole\Taxonomy\Vocabulary as Vocabulary;

/**
 * Class Taxonomy.
 */
class Taxonomy implements GeneratorInterface
{
    /* @var Data */
    protected $options;
    /* @var TaxonomyCollection */
    protected $taxonomies;
    /* @var PageCollection */
    protected $pageCollection;
    /* @var array */
    protected $siteTaxonomies;
    /* @var PageCollection */
    protected $generatedPages;

    /**
     * Taxonomy constructor.
     *
     * @param Data $options
     */
    public function __construct(Data $options)
    {
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(PageCollection $pageCollection, \Closure $messageCallback)
    {
        $this->pageCollection = $pageCollection;
        $this->generatedPages = new PageCollection();

        if (array_key_exists('taxonomies', $this->options->get('site'))) {
            $this->siteTaxonomies = $this->options->get('site.taxonomies');

            // is taxonomies disabled
            if (array_key_exists('disabled', $this->siteTaxonomies) && $this->siteTaxonomies['disabled']) {
                return $this->generatedPages;
            }

            // prepares taxonomies collection
            $this->taxonomies = new TaxonomyCollection('taxonomies');
            // adds each vocabulary collection to the taxonomies collection
            foreach ($this->siteTaxonomies as $vocabulary) {
                if ($vocabulary != 'disable') {
                    $this->taxonomies->add(new Vocabulary($vocabulary));
                }
            }

            // collects taxonomies from pages
            $this->collectTaxonomiesFromPages();

            // creates node pages
            $this->createNodePages();
        }

        return $this->generatedPages;
    }

    /**
     * Collects taxonomies from pages.
     */
    protected function collectTaxonomiesFromPages()
    {
        /* @var $page Page */
        foreach ($this->pageCollection as $page) {
            foreach ($this->siteTaxonomies as $plural => $singular) {
                if (isset($page[$plural])) {
                    // converts a list to an array if necessary
                    if (!is_array($page[$plural])) {
                        $page->setVariable($plural, [$page[$plural]]);
                    }
                    foreach ($page[$plural] as $term) {
                        // adds each terms to the vocabulary collection
                        $this->taxonomies->get($plural)
                            ->add(new Term($term));
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

    /**
     * Creates node pages.
     */
    protected function createNodePages()
    {
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
                        $pages = $pages->sortByDate()->toArray();
                        $page = (new Page())
                            ->setId(Page::urlize(sprintf('%s/%s/index', $plural, $term)))
                            ->setPathname(Page::urlize(sprintf('%s/%s', $plural, $term)))
                            ->setTitle(ucfirst($term))
                            ->setNodeType(NodeType::TAXONOMY)
                            ->setVariable('pages', $pages)
                            ->setVariable('singular', $this->siteTaxonomies[$plural])
                            ->setVariable('pagination', ['pages' => $pages]);
                        $this->generatedPages->add($page);
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
                    ->setNodeType(NodeType::TERMS)
                    ->setVariable('plural', $plural)
                    ->setVariable('singular', $this->siteTaxonomies[$plural])
                    ->setVariable('terms', $terms);

                // add page only if a template exist
                try {
                    $this->generatedPages->add($page);
                } catch (\Exception $e) {
                    echo $e->getMessage()."\n";
                    // do not add page
                    unset($page);
                }
            }
        }
    }
}
