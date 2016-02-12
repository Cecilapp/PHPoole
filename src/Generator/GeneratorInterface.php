<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Generator;

use PHPoole\Page\Collection as PageCollection;

/**
 * Interface GeneratorInterface.
 */
interface GeneratorInterface
{
    /**
     * @param  PageCollection $pageCollection
     *
     * @return array
     */
    public function Generate(PageCollection $pageCollection);
}
