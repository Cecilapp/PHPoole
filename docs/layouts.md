# Layouts

A layout is a [Twig](http://twig.sensiolabs.org) template.

## Layout fallback logic

[_WIP_](https://github.com/Narno/PHPoole-library/blob/master/src%2FPHPoole.php#L752).

## Layout variables

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

## Layout example

```html
<h1>{{ page.title }} | {{ site.title }}</h1>
<span>{{ page.date|date("j M Y") }}</span>
<b>{{ page.content }}</p>
<b>{{ page.customvar }}</p>
```

## Twig

### Twig functions

#### _url_

Creates an URL.
```
{{ url('tags/' ~ tag) }}
{{ url(page) }}
```

### Twig filters

#### _excerpt_

Truncates a string to 450 char and add '…'.
```
{{ string|excerpt }}
```

#### _sortByWeight_

Sorts a menu entries collection by weight.
```
{{ menu|sortByWeight }}
```

#### _sortByDate_

Sorts a pages collection by date.
```
{{ pages|sortByDate }}
```

#### _bySection_

Filters a pages collection by section name.
```
{{ pages|bySection('blog') }}
```
