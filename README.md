A PHP library to generate a static website. WIP.
===============

_PHPoole-library_ is a static website generator built on PHP, inspired by [Jekyll](http://jekyllrb.com/) and [Hugo](http://gohugo.io/).

It converts [Markdown](http://daringfireball.net/projects/markdown/) files into a static HTML web site, with the help of [Twig](http://twig.sensiolabs.org), a flexible and fast template engine.

You can easily create a blog, a personal website, a simple corporate website, etc.

Features
--------

* No database, files only
* Fully configurable (Through options and plugins system)
* HTML templates ([Twig](http://twig.sensiolabs.org/doc/templates.html))
* Menu
* Taxonomy (Vocablulary > Term > pages collection)

Requirements
------------

Please see the [composer.json](composer.json) file.

Installation
------------

Run the following Composer command:

    $ composer require narno/phpoole-library:1.0.X-dev

Demo
----

Try the [demo](https://github.com/Narno/PHPoole-demo)


Usage
-----

### Basic usage

First create a new directory (ie "mywebsite") with the following files structure:
```
./mywebsite
|- content             <- Contains the Mardown files
|  |- Blog             <- A 'section' named "Blog"
|  |  |- Post 1.md     <- A content page
|  |  \- Post 2.md
|  |- Project          <- A 'section' named "Project"
|  |  |- Project 1.md
|  |  \- Project 2.md
|  \- About.md
|- layouts             <- Contains the Twig templates
|  |- _default         <- Contains the default templates
|  |  |- list.html     <- Used by a node type 'list'
|  |  |- page.html     <- Used by a node type 'page'
|  |  |- taxonomy.html <- Used by a node type 'taxonomy'
|  |  \- terms.html    <- Used by a node type 'terms'
|  |- index.html       <- Used by the node type 'homepage'
\- static              <- Contains the static files
```

Then create and run the following PHP script:
```php
<?php
require_once 'vendor/autoload.php';
use PHPoole\PHPoole;

$phpoole = new PHPoole(
    './mywebsite',  // The source directory
    null,           // The destination directory
    [               // Options array
        'site' => [
            'title'   => "My website",                        // The Site title
            'baseurl' => 'http://localhost/mywebsite/_site/', // The Site base URL
        ],
    ]
);
$phpoole->build();
```

The result is a new static website created in _./mywebsite/_site_.

### Page example

```yml
---
title: "The title"
date: "2013-01-01"
myvar: "My varm"
---
Markdown page content.
```

### Layout example

```html
<h1>{{ page.title }}</h1>
<span>{{ page.date|date("j M Y") }}</span>
<b>{{ page.content }}</p>
<b>{{ page.myvar }}</p>
```
