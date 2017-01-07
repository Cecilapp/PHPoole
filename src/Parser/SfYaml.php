<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Parser;

use PHPoole\Exception\Exception;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Class SfYaml.
 */
class SfYaml implements ParserInterface
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public static function parse($string)
    {
        try {
            return Yaml::parse($string);
        } catch (ParseException $e) {
            //throw new Exception($e->getMessage());
        }
    }
}
