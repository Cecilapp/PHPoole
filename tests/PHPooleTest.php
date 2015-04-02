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
use PHPoole\Page\Converter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class PHPooleTest extends \PHPUnit_Framework_TestCase
{
    protected $sourceDir;
    protected $destDir;

    /*
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
    */

    public function testCreate()
    {
        $this->assertInstanceOf('PHPoole\PHPoole', PHPoole::create());
    }

    public function createContentIterator()
    {
        return Finder::create()
            ->files()
            ->in(__DIR__ . '/fixtures/content')
            ->name('*.md');
    }

    public function testContentIterator()
    {
        $iterator = $this->createContentIterator();
        $this->assertInstanceOf('Symfony\Component\Finder\Finder', $iterator);
        $this->assertCount(1, $iterator);
        $this->assertContainsOnlyInstancesOf('Symfony\Component\Finder\SplFileInfo', $iterator);
    }

    public function testParsePage()
    {
        foreach($this->createContentIterator() as $file) {
            $parsed = (new Page($file))->parse();
            $this->assertStringEqualsFile(__DIR__ . '/fixtures/content_parsed/Page1.md/frontmatter.yaml', $parsed->getFrontmatter());
            $this->assertStringEqualsFile(__DIR__ . '/fixtures/content_parsed/Page1.md/body.md', $parsed->getBody());
        }
    }

    public function testAddPageToCollection()
    {
        foreach($this->createContentIterator() as $file) {
            $page = new Page($file);
            $pageCollection = new PageCollection();
            $addResult = $pageCollection->add($page);
            $this->assertArrayHasKey('section1/page1', $addResult);
        }
    }

    public function testConvertYaml()
    {
        foreach($this->createContentIterator() as $file) {
            $page = new Page($file);
            $page->parse();
            $variables = (new Converter())
                ->convertFrontmatter(
                    $page->getFrontmatter(),
                    'yaml'
                );
            $this->assertArrayHasKey('title', $variables);
            $this->assertArrayHasKey('date', $variables);
        }
    }

    public function testConvertMarkdown()
    {
        foreach($this->createContentIterator() as $file) {
            $page = new Page($file);
            $page->parse();
            $html = (new Converter())
                ->convertBody($page->getBody());
            $this->assertSame('<p>Content of page 1.</p>', $html);
        }
    }

    public function testConvertPage()
    {
        $pageCollection = new PageCollection();
        foreach($this->createContentIterator() as $file) {
            $page = new Page($file);
            $page->parse();
            $pageCollection->add($page);
            $page = PHPoole::create()->convertPage($page, 'yaml');
            $pageCollection->replace($page->getId(), $page);
        }
        foreach($pageCollection as $page) {
            $this->assertObjectHasAttribute('title', $page);
            $this->assertObjectHasAttribute('html', $page);
            $this->assertObjectHasAttribute('variables', $page);
            $this->assertSame('Page 1', $page->getTitle());
            $this->assertSame('<p>Content of page 1.</p>', $page->getContent());
            //$this->assertEquals(1427839200, $page['date'], '', 5);
        }
    }
}