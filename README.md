# ![PHPoole logo](https://avatars2.githubusercontent.com/u/5618939?s=50 "Logo created by Cards Against Humanity") PHPoole-library

> An intuitive PHP library to create a static Website.

[![Build Status](https://travis-ci.org/Narno/PHPoole-library.svg?branch=develop)](https://travis-ci.org/Narno/PHPoole-library)
[![Coverage Status](https://coveralls.io/repos/github/Narno/PHPoole-library/badge.svg?branch=develop)](https://coveralls.io/github/Narno/PHPoole-library?branch=develop)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Narno/PHPoole-library/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/Narno/PHPoole-library/?branch=develop)
[![Codacy Badge](https://api.codacy.com/project/badge/grade/adbaa5309cd749fc9e095ca47d347586)](https://www.codacy.com/app/Narno/PHPoole-library)
[![StyleCI](https://styleci.io/repos/32327575/shield)](https://styleci.io/repos/32327575)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/f4c44315-d370-499e-8f61-d6d1ce0cadde/mini.png)](https://insight.sensiolabs.com/projects/f4c44315-d370-499e-8f61-d6d1ce0cadde)

_PHPoole-library_ is inspired by [Jekyll](http://jekyllrb.com/) and [Hugo](http://gohugo.io/).

It converts [Markdown](http://daringfireball.net/projects/markdown/) files into a static HTML web site, with the help of [Twig](http://twig.sensiolabs.org), a flexible and fast template engine.

You can easily create a blog, a personal website, a simple corporate website, etc.

## Features

* No database, files only (host your site anywhere)
* Fully configurable (Through options and plugins system - _WIP_)
* Flexible template engine ([Twig](http://twig.sensiolabs.org/doc/templates.html))
* Theme support
* Dynamic menu creation
* Configurable taxonomies (categories, tags, etc.)
* Paginator (for homepage, sections and taxonomies)

## Requirements

Please see the [composer.json](composer.json) file.

## Installation

### Manually

[Download](http://narno.org/PHPoole-library/phpoole-library.phar) the Phar (_not up to date_).

### Composer

Run the following command:

    $ composer require narno/phpoole-library:1.0.X-dev

## Demo

Try the [demo](https://github.com/Narno/PHPoole-demo).

## Usage

### Overview

To create a new website, you need 3 things:
 * pages (Markdown files)
 * templates (Twig files)
 * a build script (PHP script)

Organize your content:
```
.
├─ content               <- Contains Mardown files
|  ├─ Blog               <- A section named "Blog"
|  |  ├─ Post 1.md       <- A page in the "Blog" section
|  |  └─ Post 2.md       <- A page in the "Blog" section
|  ├─ Project            <- A section named "Project"
|  |  └─ Project 1.md    <- A page in the "Project" section
|  └─ About.md           <- A page in the root
├─ layouts               <- Contains Twig templates
|  ├─ _default           <- Contains default templates
|  |  ├─ list.html       <- Used by a _list_ node type (ie: "section")
|  |  └─ page.html       <- Used by the _page_ node type
|  └─ index.html         <- Used by the _homepage_ node type
└─ static                <- Contains static files
   └─ robots.txt         <- A static file
```

Create a PHP script:
```php
<?php
date_default_timezone_set('Europe/Paris'); // default time zone
require_once 'vendor/autoload.php'; // Composer
//require_once 'phar://phpoole-library.phar'; // Phar file
use PHPoole\PHPoole;

PHPoole::create(
    './', // The source directory
    null, // The destination directory ("null" = the same as source)
    [     // Options array
        'site' => [
            'title'   => "My website",             // The Website title
            'baseurl' => 'http://localhost:8000/', // The Website base URL
        ],
    ]
)->build(); // Launch the builder

exec('php -S localhost:8000 -t _site'); // Run a local server
```

By default, the static website is created in the _./_site_ directory:
```
./_site
├─ blog
|  ├─ post-1
|  |  └─ index.html
|  ├─ post-2
|  |  └─ index.html
|  └─ index.html
├─ project
|  ├─ project-1
|  |  └─ index.html
|  └─ index.html
├─ about
|  └─ index.hml
├─ index.html
└─ robots.txt
```

### Content

The content is represented by [Markdown](http://daringfireball.net/projects/markdown/) files organized in folders.
Folders in the root are called "section" (ie: "Blog", "Project", etc.).

#### Pages 

A page can contain a [front matter](#page-front-matter) ([YAML](http://www.yaml.org/spec/1.2/spec.html#Preview)) and/or a [body](#page-body) ([Markdown](http://daringfireball.net/projects/markdown/syntax)).

##### Page front matter

Any file that contains a YAML front matter will be processed to extract some variables. The front matter must be the first thing in the file and must be a valid YAML.

**Predefined variables**

| Variable      | Description   | Default value                     |
| ------------- | ------------- | --------------------------------- |
| title         | Title         | File basename (without extension) |
| section       | Section       | Root directory of the file path   |
| layout        | Layout        | See [_Layout fallback logic_](#layout-fallback-logic) |
| date          | Date          | File modification time (_To Do_)  |
| menu          | Menu          | Homepage and sections             |
| tags          | Tags          | _Empty_                           |
| categories    | Categories    | _Empty_                           |
| permalink     | Permalink     | _To Do_                           |

##### Page body

Body is the main content of the page, it could be in Markdown or in plain text.

##### Page example

```yml
---
title: "The title"
date: "2013-01-01"
customvar: "Value of customvar"
---
_Markdown_ page content.
```

### Layouts

A layout is a [Twig](http://twig.sensiolabs.org) template.

#### Layout fallback logic

[_WIP_](https://github.com/Narno/PHPoole-library/blob/master/src%2FPHPoole.php#L880).

#### Layout variables

**Site variables**

Contains all variables under _site_ key in config array (see [_Default options_](#default-options)).

| Variable      | Description       |
| ------------- | ----------------- |
| site.menus    | Menus collections |
| site.pages    | Pages collections |

Menu entry keys:

| Variable               | Description  |
| ---------------------- | ------------ |
| site.menus._id_.name   | Entry name   |
| site.menus._id_.url    | Entry URL    |
| site.menus._id_.weight | Entry weight |

**Page variables**

Contains all variables setted in the page's [front matter](#page-front-matter).

| Variable        | Description                      | Example       |
| --------------- | -------------------------------- | ------------- |
| page.title      | Title                            | "Post 1"      |
| page.section    | Section                          | "blog"        |
| page.id         | Unique id                        | "blog/post-1" |
| page.pathname   | Full path                        | "blog/post-1" |
| page.path       | Path                             | "blog"        |
| page.name       | Name                             | "post-1"      |
| page.pages      | Pages collection (for node page) | _Collection_  |
| page.tags       | Tags array                       | [Ta, Tb]      |
| page.categories | Categories array                 | [Ca, Cb]      |
| ...             |                                  |               |

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
<b>{{ page.customvar }}</p>
```

#### Twig functions

##### _url_

Creates an URL.
```
{{ url('tags/' ~ tag) }}
{{ url(page) }}
```

#### Twig filters

##### _excerpt_

Truncates a string to 450 char and add '…'.
```
{{ string|excerpt }}
```

##### _sortByWeight_

Sorts a menu entries collection by weight.
```
{{ menu|sortByWeight }}
```

##### _sortByDate_

Sorts a pages collection by date.
```
{{ pages|sortByDate }}
```

##### _bySection_

Filters a pages collection by section name.
```
{{ pages|bySection('blog') }}
```

### Options

PHP array of options used to define how to build the website.

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

## Packaging the Phar file

1. Install [Box](https://github.com/box-project/box2)
2. Run the following command:
```
    $ box build -v
```

## License

PHPoole-library is a free software distributed under the terms of the MIT license.
