<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Converter;

use ParsedownExtra;

/**
 * Class Parsedown.
 */
class Parsedown implements ConverterInterface
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function convert($string)
    {
        return ParsedownExtra::instance()->text($string);
    }
}
