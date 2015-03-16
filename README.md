PHPoole-library
==============

Static site builder library with events plugin system. WIP.

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
~/website
|- content
|  |- Section 1
|  |  |- File 1.md
|  |  \- File 2.md
|  \- Section 2
|  |  |- File 3.md
|  |  \- File 4.md
|- layouts
|  |- default.html
|  |- index.html
|  \- list.html
\- static
```

**PHP script**
```php
$sourceDir = $destDir = '~/website';
$options = [
    'site' => [
        'title'   => "PHPoole's website",
        'baseurl' => 'http://localhost:63342/PHPoole-library/demo/site/',
    ]
];
$phpoole = new PHPoole($sourceDir, $destDir, $options);
$phpoole->build();
```

The result is a new static website created in _~/website/site_.
