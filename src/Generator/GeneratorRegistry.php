<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Generator;

class GeneratorRegistry extends \SplObjectStorage
{
    /**
     * Check if a generator is registered.
     *
     * @param GeneratorInterface $generator
     *
     * @return bool
     */
    public function hasGenerator(GeneratorInterface $generator)
    {
        return parent::contains($generator);
    }

    /**
     * Register a generator.
     *
     * @param GeneratorInterface $generator
     * @param int                $priority
     *
     * @throws \LogicException
     *
     * @return self
     */
    public function addGenerator(GeneratorInterface $generator, $priority = 1)
    {
        if (parent::contains($generator)) {
            throw new \LogicException(sprintf(
                'Generator of type "%s" already registered',
                get_class($generator)
            ));
        }
        parent::attach($generator);

        return $this;
    }

    /**
     * Remove an already registered generator.
     *
     * @param GeneratorInterface $generator
     *
     * @return self
     */
    public function removeGenerator(GeneratorInterface $generator)
    {
        if (parent::contains($generator)) {
            parent::detach($generator);
        }

        return $this;
    }
}
