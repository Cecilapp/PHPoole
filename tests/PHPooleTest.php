<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\PHPooleTest;

use PHPoole\PHPoole;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class PHPooleTest extends \PHPUnit_Framework_TestCase
{
    protected $sourceDir;
    protected $destDir;

    public function setUp()
    {
        $this->sourceDir = (__DIR__ . '/fixtures/website');
        $this->destDir   = $this->sourceDir;
    }

    public function tearDown()
    {
        $fs = new Filesystem();
        $fs->remove($this->destDir . '/_site');
    }

    public function testCreate()
    {
        $this->assertInstanceOf('PHPoole\PHPoole', PHPoole::create());
    }

    public function testLocateContent()
    {
        $iterator = Finder::create()
            ->files()
            ->in($this->sourceDir . '/content')
            ->name('*.md');
        $this->assertInstanceOf('Symfony\Component\Finder\Finder', $iterator);
        $this->assertCount(1, $iterator);
        $this->assertContainsOnlyInstancesOf('Symfony\Component\Finder\SplFileInfo', $iterator);
    }
}