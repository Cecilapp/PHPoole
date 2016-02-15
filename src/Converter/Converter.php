<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Converter;

use ParsedownExtra;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Converter.
 */
class Converter implements ConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function convertFrontmatter($string, $type = 'yaml')
    {
        switch ($type) {
            case 'ini':
                return parse_ini_string($string);
            case 'yaml':
            default:
                return Yaml::parse($string);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function convertBody($string)
    {
        return (new ParsedownExtra())->text($string);
    }
}
