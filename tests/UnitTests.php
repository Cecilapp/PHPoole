<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Test;

use Dflydev\DotAccessData\Data;
use PHPoole\Converter\Converter;
use PHPoole\Page\Collection as PageCollection;
use PHPoole\Page\Page;
use PHPoole\PHPoole;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class UnitTests extends \PHPUnit_Framework_TestCase
{
    protected $sourceDir;
    protected $destDir;
    protected $iterator;
    protected $file;

    public function setUp()
    {
        $this->sourceDir = (__DIR__.'/fixtures/website');
        $this->destDir = $this->sourceDir;
        $this->iterator = $this->createContentIterator();
        $this->file = $this->createFile();
    }

    public function tearDown()
    {
        $fs = new Filesystem();
        $fs->remove($this->destDir.'/_site');
    }

    public function createContentIterator()
    {
        return Finder::create()
            ->files()
            ->in(__DIR__.'/fixtures/content')
            ->name('*.md');
    }

    public function createFile()
    {
        return new SplFileInfo(
            __DIR__.'/fixtures/content/Section1/Page1.md',
            'Section1',
            'Section1/Page1.md'
        );
    }

    public function testCreate()
    {
        $this->assertInstanceOf('PHPoole\PHPoole', PHPoole::create());
    }

    public function testOptions()
    {
        $data = new Data([
            'options' => [
                'option1' => 'Option 1',
                'option2' => 'Option 2',
            ],
        ]);
        $phpoole = (new PHPoole())->setOptions($data);
        $this->assertEquals($data, $phpoole->getOptions());
    }

    public function testContentIterator()
    {
        $iterator = $this->iterator;
        $this->assertInstanceOf('Symfony\Component\Finder\Finder', $iterator);
        $this->assertCount(1, $iterator);
        $this->assertContainsOnlyInstancesOf('Symfony\Component\Finder\SplFileInfo', $iterator);
    }

    public function testParsePage()
    {
        $parsed = (new Page($this->file))->parse();
        $this->assertStringEqualsFile(__DIR__.'/fixtures/content_parsed/Page1.md/frontmatter.yaml', $parsed->getFrontmatter());
        $this->assertStringEqualsFile(__DIR__.'/fixtures/content_parsed/Page1.md/body.md', $parsed->getBody());
    }

    public function testAddPageToCollection()
    {
        $page = new Page($this->file);
        $pageCollection = new PageCollection();
        $addResult = $pageCollection->add($page);
        $this->assertArrayHasKey('section1/page1', $addResult);
    }

    public function testConvertYaml()
    {
        $page = new Page($this->file);
        $page->parse();
        $variables = (new Converter())
            ->convertFrontmatter(
                $page->getFrontmatter(),
                'yaml'
            );
        $this->assertArrayHasKey('title', $variables);
        $this->assertArrayHasKey('date', $variables);
    }

    public function testConvertMarkdown()
    {
        $page = new Page($this->file);
        $page->parse();
        $html = (new Converter())
            ->convertBody($page->getBody());
        $this->assertSame('<p>Content of page 1.</p>', $html);
    }

    public function testConvertPage()
    {
        $pageCollection = new PageCollection();

        $page = new Page($this->file);
        $page->parse();
        $pageCollection->add($page);

        $page = PHPoole::create()->convertPage($page, 'yaml');
        $pageCollection->replace($page->getId(), $page);
        unset($page);

        $page = $pageCollection->get('section1/page1');
        $this->assertObjectHasAttribute('title', $page);
        $this->assertObjectHasAttribute('html', $page);
        $this->assertObjectHasAttribute('properties', $page);
        $this->assertSame('Page 1', $page->getTitle());
        $this->assertSame('<p>Content of page 1.</p>', $page->getContent());
        //$this->assertEquals(1427839200, $page['date'], '', 5);}
    }

    public function testAddPage()
    {
        $page = new Page();
        $pageCollection = new PageCollection();

        $page->setId('id-of-page')
            ->setTitle('title-of-page');
        $pageCollection->add($page);

        $this->assertContains($page, $pageCollection);
    }

    public function testGetPage()
    {
        $page = new Page();
        $pageCollection = new PageCollection();

        $page->setId('id-of-page')
            ->setTitle('title-of-page');
        $pageCollection->add($page);

        $this->assertNotNull($pageCollection->get('id-of-page'));
    }
}
