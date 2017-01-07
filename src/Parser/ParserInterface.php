<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Parser;

/**
 * Interface ParserInterface.
 */
interface ParserInterface
{
    /**
     * Parse string.
     *
     * @param  $string
     */
    public static function parse($string);
}
