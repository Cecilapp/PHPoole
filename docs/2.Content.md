# Content

The content is represented by [Markdown](http://daringfireball.net/projects/markdown/) files organized in folders.
Folders in the root are called "section" (ie: "Blog", "Project", etc.).

## Pages 

A page can contain a [front matter](#page-front-matter) ([YAML](http://www.yaml.org/spec/1.2/spec.html#Preview)) and/or a [body](#page-body) ([Markdown](http://daringfireball.net/projects/markdown/syntax)).

### Page front matter

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

### Page body

Body is the main content of the page, it could be in Markdown or in plain text.

### Page example

```yml
---
title: "The title"
date: "2013-01-01"
customvar: "Value of customvar"
---
_Markdown_ page content.
```
