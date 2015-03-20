PHPoole-library - A PHP library to generate a static website. WIP.
===============

_PHPoole-library_ is a static website generator built on PHP, inspired by [Jekyll](http://jekyllrb.com/) and [Hugo](http://gohugo.io/).

It converts [Markdown](http://daringfireball.net/projects/markdown/) files into a static HTML web site, with the help of [Twig](http://twig.sensiolabs.org), a flexible and fast template engine.

You can easily create a blog, a personal website, a simple corporate website, etc.

Requirements
------------

Please see the [composer.json](composer.json) file.

Installation
------------

Run the following Composer command:

    $ composer require "narno/phpoole-library": "dev-master"

Usage
-----

### Try the demo

```php
<?php
require_once 'vendor/autoload.php';

use PHPoole\PHPoole;

PHPoole::create('./demo', null, ['theme' => 'hyde'])->build();
```

### Basic usage

**Files tree**
```
./project
|- content
|  |- Section 1
|  |  |- File 2.md
|  |  \- File 3.md
|  |- Section 2
|  |  |- File 4.md
|  |  \- File 5.md
|  \- File 1.md
|- layouts
|  |- _default
|  |  |- list.html
|  |  |- page.html
|  |  \- terms.html
|  |- index.html
\- static
```

**PHP script**
```php
<?php
require_once 'vendor/autoload.php';
use PHPoole\PHPoole;

$phpoole = new PHPoole('./project', null, [
    'site' => [
        'title'   => "PHPoole's website",
        'baseurl' => 'http://localhost/project/_site/',
    ],
]);
$phpoole->build();
```

The result is a new static website created in _./project/_site_.
