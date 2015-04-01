<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\PHPooleTest;

use PHPoole\PHPoole;
use PHPoole\Page\Page;
use PHPoole\Page\Collection as PageCollection;
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

    public function createContentIterator()
    {
        return Finder::create()
            ->files()
            ->in($this->sourceDir . '/content')
            ->name('*.md');
    }

    public function testLocateContent()
    {
        $iterator = $this->createContentIterator();
        $this->assertInstanceOf('Symfony\Component\Finder\Finder', $iterator);
        $this->assertCount(1, $iterator);
        $this->assertContainsOnlyInstancesOf('Symfony\Component\Finder\SplFileInfo', $iterator);
    }

    public function testBuildPagesFromContent()
    {
        foreach($this->createContentIterator() as $file) {
            $this->assertInstanceOf('Symfony\Component\Finder\SplFileInfo', $file);

            $page = (new Page($file));
            $this->assertInstanceOf('PHPoole\Page\Page', $page);

            $parsed = $page->parse();
            //$this->assertArrayHasKey('title', $parsed->getFrontmatter());
            $this->assertSame("title: Page 1\r\ndate: 2015-04-01", $parsed->getFrontmatter());
            $this->assertSame('Content of page 1.', $parsed->getBody());

            $pageCollection = new PageCollection();
            $this->assertInstanceOf('PHPoole\Page\Collection', $pageCollection);
            $addResult = $pageCollection->add($page);
            $this->assertInstanceOf('PHPoole\Page\Collection', $addResult);
        }
    }
}