<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Parser;

/**
 * Class Ini.
 */
class Ini implements ParserInterface
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public static function parse($string)
    {
        return parse_ini_string($string);
    }
}
