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
use PHPoole\Page\NodeTypeEnum;
use PHPoole\Page\Page;

/**
 * Class Taxonomy.
 */
class Taxonomy implements GeneratorInterface
{
    /* @var Data $options */
    protected $options;
    /* @var \PHPoole\Taxonomy\Collection $taxonomies */
    protected $taxonomies;

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
        $generatedPages = new PageCollection();

        if (array_key_exists('taxonomies', $this->options->get('site'))) {
            // collects taxonomies from pages
            $this->taxonomies = new \PHPoole\Taxonomy\Collection();
            $siteTaxonomies = $this->options->get('site.taxonomies');
            // adds each vocabulary collection to the taxonomies collection
            foreach ($siteTaxonomies as $plural => $singular) {
                $this->taxonomies->add(new \PHPoole\Taxonomy\Vocabulary($plural));
            }
            /* @var $page Page */
            foreach ($pageCollection as $page) {
                foreach ($siteTaxonomies as $plural => $singular) {
                    if (isset($page[$plural])) {
                        // converts a list to an array if necessary
                        if (!is_array($page[$plural])) {
                            $page->setVariable($plural, [$page[$plural]]);
                        }
                        foreach ($page[$plural] as $term) {
                            // adds each terms to the vocabulary collection
                            $this->taxonomies->get($plural)
                                ->add(new \PHPoole\Taxonomy\Term($term));
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
                        if (!$pageCollection->has($term)) {
                            $pages = $pages->sortByDate()->toArray();
                            $page = (new Page())
                                ->setId(Page::urlize(sprintf('%s/%s/index', $plural, $term)))
                                ->setPathname(Page::urlize(sprintf('%s/%s', $plural, $term)))
                                ->setTitle(ucfirst($term))
                                ->setNodeType(NodeTypeEnum::TAXONOMY)
                                ->setVariable('pages', $pages)
                                ->setVariable('singular', $siteTaxonomies[$plural])
                                ->setVariable('pagination', ['pages' => $pages]);
                            $generatedPages->add($page);
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
                        $generatedPages->add($page);
                    } catch (\Exception $e) {
                        echo $e->getMessage()."\n";
                        // do not add page
                        unset($page);
                    }
                }
            }
        }

        return $generatedPages;
    }
}
