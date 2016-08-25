<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Generator;

/**
 * Interface GeneratorInterface.
 */
interface GeneratorInterface
{
    /**
     * Give Options to object.
     *
     * @param \PHPoole\Options $options
     *
     * @return void
     */
    public function __construct(\PHPoole\Options $options);

    /**
     * @param \PHPoole\Page\Collection $pageCollection
     * @param \Closure                 $messageCallback
     *
     * @return \PHPoole\Page\Collection
     */
    public function generate(\PHPoole\Page\Collection $pageCollection, \Closure $messageCallback);
}
