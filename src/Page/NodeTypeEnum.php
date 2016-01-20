<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Page;

use MyCLabs\Enum\Enum;

/**
 * Class PageNodeTypeEnum.
 */
class NodeTypeEnum extends Enum
{
    //const PAGE = 'page';
    const HOMEPAGE = 'homepage';
    const SECTION = 'section';
    const TAXONOMY = 'taxonomy';
    const TERMS = 'terms';
}
