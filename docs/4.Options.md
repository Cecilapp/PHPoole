# Options

PHP array of options used to define how to build the website.

## Default options

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
