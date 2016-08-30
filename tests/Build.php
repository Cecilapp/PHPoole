<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Test;

use PHPoole\PHPoole;
use Symfony\Component\Filesystem\Filesystem;

class Build extends \PHPUnit_Framework_TestCase
{
    protected $wsSourceDir;
    protected $wsDestinationDir;

    public function setUp()
    {
        $this->wsSourceDir = __DIR__.'/fixtures/website';
        $this->wsDestinationDir = $this->wsSourceDir;
    }

    public function tearDown()
    {
        $fs = new Filesystem();
        $fs->remove($this->wsDestinationDir.'/_site');
        $fs->remove(__DIR__.'/../_cache');
    }

    public function testBuid()
    {
        PHPoole::create(
            [
                'site' => [
                    'menu' => [
                        'main' => [
                            'index' => [
                                'id'     => 'index',
                                'name'   => 'Da home!',
                                'url'    => '',
                                'weight' => 1,
                            ],
                            'about' => [
                                'id'       => 'about',
                                'disabled' => true,
                            ],
                            'narno' => [
                                'id'     => 'narno',
                                'name'   => 'narno.org',
                                'url'    => 'http://narno.org',
                                'weight' => 999,
                            ],
                        ],
                    ],
                    'paginate' => [
                        'disabled' => false,
                        'homepage' => [
                            'section' => 'blog',
                        ],
                    ],
                    'taxonomies' => [
                        'disabled' => false,
                    ],
                ],
                'theme' => 'theme',
                'static' => [
                    'exclude' => [
                        'test.txt',
                    ],
                ],
                'generators' => [
                    99 => 'Pouet',
                ],
            ]
        )->setSourceDir($this->wsSourceDir)
        ->setDestinationDir($this->wsDestinationDir)
        ->build();
    }
}
