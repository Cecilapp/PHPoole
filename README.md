PHPoole-library
===============

Static site builder library (with events plugin system). WIP.

Installation
------------

1. Clone or download the repository
2. Install dependencies through [Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)

Usage
-----

### Try the demo

```php
<?php
require_once 'vendor/autoload.php';

use PHPoole\PHPoole;

PHPoole::create('./demo')->build();
```

### Basic usage

**Files tree**
```
~/project
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
    'theme' => 'hyde'
]);
$phpoole->build();
```

The result is a new static website created in _./project/_site_.
