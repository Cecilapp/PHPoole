![PHPoole logo](https://avatars2.githubusercontent.com/u/5618939?s=50 "Logo created by Cards Against Humanity") PHPoole-library
=============

> A PHP library to generate a static website. WIP.

[![Build Status](https://travis-ci.org/Narno/PHPoole-library.svg?branch=master)](https://travis-ci.org/Narno/PHPoole-library)
[![Code Coverage](https://scrutinizer-ci.com/g/Narno/PHPoole-library/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Narno/PHPoole-library/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Narno/PHPoole-library/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Narno/PHPoole-library/?branch=master)
[![Code Climate](https://codeclimate.com/github/Narno/PHPoole-library/badges/gpa.svg)](https://codeclimate.com/github/Narno/PHPoole-library)
[![Dependency Status](https://www.versioneye.com/user/projects/551b20523661f134fe0001eb/badge.svg?style=flat)](https://www.versioneye.com/user/projects/551b20523661f134fe0001eb)

_PHPoole-library_ is a static website generator built on PHP, inspired by [Jekyll](http://jekyllrb.com/) and [Hugo](http://gohugo.io/).

It converts [Markdown](http://daringfireball.net/projects/markdown/) files into a static HTML web site, with the help of [Twig](http://twig.sensiolabs.org), a flexible and fast template engine.

You can easily create a blog, a personal website, a simple corporate website, etc.

Features
--------

* No database, files only (host your site anywhere)
* Fully configurable (Through options and plugins system) _WIP_
* Flexible template engine ([Twig](http://twig.sensiolabs.org/doc/templates.html))
* Theme support
* Dynamic menu creation
* Configurable taxonomies (categories, tags, etc.)
* Paginator (for homepage, sections and taxonomy)

Requirements
------------

Please see the [composer.json](composer.json) file.

Installation
------------

### Manually

[Download](http://narno.org/PHPoole-library/phpoole-library.phar) the Phar (not up to date)

### Composer

Run the following command:

    $ composer require narno/phpoole-library:1.0.X-dev

Demo
----

Try the [demo](https://github.com/Narno/PHPoole-demo)

Usage
-----

### Overview

To create a new website, you need 3 things:
 * pages (content)
 * templates (layouts)
 * a build script (PHP)

Organize your content:
```
.
├─ content             <- Contains Mardown files
|  ├─ Blog             <- A section named "Blog"
|  |  └─ Post 1.md     <- A page in the section "Blog"
|  ├─ Project          <- A section named "Project"
|  |  └─ Post 1.md     <- A page in the section "Project"
|  └─ About.md         <- A page in the root
├─ layouts             <- Contains Twig templates
|  ├─ _default         <- Contains default templates
|  |  ├─ list.html     <- Used by a node type 'list'
|  |  ├─ page.html     <- Used by a node type 'page'
|  ├─ index.html       <- Used by the node type 'homepage'
└─ static              <- Contains static files
```

Create a PHP script:
```php
<?php
date_default_timezone_set('Europe/Paris');
require_once 'vendor/autoload.php'; // Composer
//require_once 'phar://phpoole-library.phar'; // Phar
use PHPoole\PHPoole;

PHPoole::create(
    './', // The source directory
    null, // The destination directory (the same as source)
    [     // Options array
        'site' => [
            'title'   => "My website",             // The Site title
            'baseurl' => 'http://localhost:8000/', // The Site base URL
        ],
    ]
)->build(); // Launch builder

exec('php -S localhost:8000 -t _site'); // Run a local server
```

The static website is created in _./_site_.

### Content

The content is represented by Markdown files organized in folders.
Folders in the root are called "section" (ie: "Blog", "Project", etc.).

A page can contain a front matter ([YAML](http://www.yaml.org/spec/1.2/spec.html#Preview)) and/or a body ([Markdown](http://daringfireball.net/projects/markdown/syntax)).

#### Front matter

Any file that contains a YAML front matter will be processed to extract some variables. The front matter must be the first thing in the file and must be a valid YAML.

**Predefined variables**

| Variable      | Description   | Default value                     |
| ------------- | ------------- | --------------------------------- |
| title         | Title         | File basename (without extension) |
| section       | Section       | Root directory of the file path   |
| layout        | Layout        | See _Layout fallback logic_       |
| date          | Date          |                                   |
| menu          | Menu          |                                   |
| tags          | Tags          |                                   |
| categories    | Categories    |                                   |
| permalink     | Permalink     |                                   |

#### Page example

```yml
---
title: "The title"
date: "2013-01-01"
customvar: "Custom variable"
---
_Markdown_ page content.
```

### Layouts

A layout is a [Twig](http://twig.sensiolabs.org) template.

#### Variables

**Site variables**

Contains all variables under _site_ key in config array (see _Default options_).

| Variable      | Description       |
| ------------- | ----------------- |
| site.menus    | Menus collections |
| site.pages    | Pages collections |

Menu keys:
* site.menus._id_.name
* site.menus._id_.url
* site.menus._id_.weight

**Page variables**

Contains all variables setted in the page's front matter.

| Variable      | Description      | Example       |
| ------------- | ---------------- | ------------- |
| title         | Title            | "Post 1"      |
| section       | Section          | "blog"        |
| id            | Unique id        | "blog/post-1" |
| pathname      | Full path        | "blog/post-1" |
| path          | Path             | "blog"        |
| name          | Name             | "post-1"      |
| pages         | Pages collection | _Collection_  |
| tags          | Tags array       | [TA, TB]      |
| categories    | Categories array | [CA, CB]      |
| ...           |                  |               |

**Paginator variables**

| Variable       | Description               |
| -------------- | ------------------------- |
| paginator.prev | Path to the previous page |
| paginator.next | Path to the next page     |

**PHPoole variables**

| Variable          | Description                 |
| ----------------- | --------------------------- |
| phpoole.url       | URL to the official website |
| phpoole.version   | Current version             |  
| phpoole.poweredby | "PHPoole v" + version       |

#### Layout example

```html
<h1>{{ page.title }} | {{ site.title }}</h1>
<span>{{ page.date|date("j M Y") }}</span>
<b>{{ page.content }}</p>
<b>{{ page.myvar }}</p>
```

### Options

PHP script options to define how to build the website.

#### Default options

```php
[
    'site' => [
        'title'       => 'PHPoole', // site title
        'baseline'    => 'A PHPoole website', // site baseline
        'baseurl'     => 'http://localhost:8000/', // php -S localhost:8000 -t _site/ >/dev/null
        'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', // site description
        'taxonomies'  => [ // list of taxonomies
            'tags'       => 'tag',      // tag vocabulary
            'categories' => 'category', // category vocabulary
        ],
        'paginate' => [ // pagination options
            'max'  => 5,      // maximum numbers of listed pages
            'path' => 'page', // ie: section/page/2
        ],
    ],
    'content' => [
        'dir' => 'content', // content directory (from source)
        'ext' => 'md',      // file extension (*.md)
    ],
    'frontmatter' => [
        'format' => 'yaml', // yaml or ini
    ],
    'body' => [
        'format' => 'md', // body format, Markdown by default
    ],
    'static' => [
        'dir' => 'static', // static files directory
    ],
    'layouts' => [
        'dir' => 'layouts', // layouts/templates files directory
    ],
    'output' => [
        'dir'      => '_site',      // output directory
        'filename' => 'index.html', // default filename of generated files
    ],
    'themes' => [
        'dir' => 'themes', // themes directory
    ],
]
```
