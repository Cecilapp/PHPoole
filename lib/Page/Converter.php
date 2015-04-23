<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Page;

use Symfony\Component\Yaml\Yaml;
use ParsedownExtra;

/**
 * Class Converter
 * @package PHPoole\Page
 */
class Converter
{
    /**
     * Converts frontmatter
     *
     * @param $string
     * @param string $type
     * @return array
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
     * Converts body
     *
     * @param $string
     * @return string
     */
    public function convertBody($string)
    {
        return (new ParsedownExtra())->text($string);
    }
}
