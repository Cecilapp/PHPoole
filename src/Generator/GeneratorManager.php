<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Generator;

use PHPoole\Page\Collection as PageCollection;

class GeneratorManager extends \SplPriorityQueue
{
    /**
     * Adds a generator.
     *
     * @param GeneratorInterface $generator
     * @param int                $priority
     *
     * @return self
     */
    public function addGenerator(GeneratorInterface $generator, $priority = 1)
    {
        $this->insert($generator, $priority);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function compare($priority1, $priority2)
    {
        if ($priority1 === $priority2) {
            return 0;
        }

        return $priority1 > $priority2 ? -1 : 1;
    }

    /**
     * Process each generators.
     *
     * @param PageCollection $pageCollection
     *
     * @return PageCollection
     */
    public function generate(PageCollection $pageCollection)
    {
        $this->top();
        while ($this->valid()) {
            /* @var GeneratorInterface $generator */
            $generator = $this->current();
            $pageCollection = $generator->generate($pageCollection);
            $this->next();
        }

        return $pageCollection;
    }
}
