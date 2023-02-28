Staccato
======

Staccato is a minimalist templating system for native PHP templates.

It borrows foundational ideas from the venerable [Twig][twig], primarily the
inheritance model. As such, it's built on the concept of extending a template
via *blocks*. Caching is provided as a first-class feature (though it's not
required), with support for *dynamic* caching enabling a cached template to
include dynamic content. A simple plugin facility ensures Staccato can be
extended as needed. Staccato ships with an included markdown plugin that uses
[Parsedown][pd].

[twig]: https://twig.symfony.com "Twig - The flexible, fast, and secure
template engine for PHP"
[pd]: https://parsedown.org/ "Parsedown - A Better Markdown Parser in PHP"

Inheritance
-----------

Got something you've been wanting to get off your chest? Create a blog! Start by
writing a simple site template called `base.tmpl.php`.

```php
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <title>The Frivolous Fancies of Feral Foxes</title>
    <link rel="stylesheet" type="text/css" href="blog.css">
  </head>
  <body>
    <header><h1>The Frivolous Fancies of Foxes</h1</header>
    <article>
      <!-- content goes here -->
    </article>
  </body>
</html>
```

Great! Now let's consider how we're going to include content. Templates can be
passed variables when they're rendered. This let's us provide the content to the
template using variables. We just need to output the variables in `<article>`
tag in the template.

```php
    <article>
      <header><h1><?= $title ?></h1></header>
      <?= $body ?>
    </article>
```

There is a built-in variable called `$_` that refers to the instance of *Template*
that represents the current template. It's use will be covered later.

Now we can render the template.

```php
use MattFerris\Staccato\Staccato;

$vars = [
    'title' => 'Are Foxes Funny or Frightening?',
    'body' => '...';
];

echo (new Staccato())->render('base.tmpl.php', $vars);
```

Of course, everything we've done so far is bog-standard PHP templating and
doesn't require a template engine. So let's make things interesting.

As you're readership has grown, you're noticing demand for specific types of
content, like reviews and more formal articles in addition to the current
informal posts. It's time to extend your template to create a distinct look
for each type of content. *Blocks* makes this quite easy.

```php
<?php namespace MattFerris\Staccato ?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <title>The Frivolous Fancies of Feral Foxes</title>
    <link rel="stylesheet" type="text/css" href="blog.css">
  </head>
  <body>
    <header><h1>The Frivolous Fancies of Foxes</h1</header>
    <article>
      <?php begin($_, 'content') ?>
      <?php end($_) ?>
    </article>
  </body>
</html>
```

To start with, we've defined a block called `content` using the Staccato
function `begin()`. All Staccato functions require you to pass the template
instance as their first argument. The instance is available as `$this`, or as
the simpler `$_` we introduced earlier. `begin()` also requires you to pass a
name for the block, in this case `content`. Finally, blocks are terminated by
`end()`.

The eagle-eyed readers out there will also notice we specified a namespace for
this template, `MattFerris\Staccato`. This is necessary to provide easy access to
the template functions. Otherwise you'd have to specify the fully-qualified
function name (e.g. `<?php MattFerris\Staccato\begin($_, 'content') ?>`). No thanks!

Next we can define a template called `article.tmpl.php` that will extend our
base template.

```php
<?php namespace MattFerris\Staccato ?>
<?php extend($_, 'base.tmpl.php') ?>

<?php begin($_, 'content') ?>
  <header><h1><?= $title ?></h1></header>
  <?= $body ?>
<?php end($_) ?>
```

We can extend `base.tmpl.php` using the `extend()` function. By doing so, we
gain access to the blocks defined in `base.tmpl.php`. Extension is accomplished
by modifying the content of the blocks in the parent template. Anything defined
in the extended block will overwrite what was defined in the parent.

Articles will need their own stylesheet. So let's define a block in
`base.tmpl.php` for stylesheets and add the existing stylesheet to it.

```php
  <head>
    <meta charset="utf8">
    <title>The Frivolous Fancies of Feral Foxes</title>
    <?php block($_, 'css') ?>
      <link rel="stylesheet" type="text/css" href="blog.css">
    <?php end($_) ?>
  </head>
```

Now we can access the `css` block in `article.tmpl.php`.

```php
<?php block($_, 'css') ?>
  <link rel="stylesheet" type="text/css" href="article.css">
<?php end($_) ?>
```

But wait, once we extend the `css` block, we'll overwrite the parent and lose
the `base.css` stylesheet. Fortunately, we can access the content of the parent
block using `parent()`.

```php
<?php block($_, 'css') ?>
  <?= parent($_) ?>
  <link rel="stylesheet" type="text/css" href="article.css">
<?php end($_) ?>
```

However, because we're only appending a stylesheet to the block, we can use a
shortcut.

```php
<?php append($_, 'css) ?>
  <link rel="stylesheet" type="text/css" href="article.css">
<?php end($_) ?>
```

The `append()` function will extend a block such that all content defined
within it is appended to the content of the parent. Likewise `prepend()` can be
used to prepend content to a parent block.

Template Inclusion
------------------

Every now and then you post a series of related blog posts, reviews, or
articles. To improve usability, you want to add some series navigation elements
to the pages that make up a series. This means you can't simply add the nav
elements to your base template. You also don't want to have to separate
implementations for each child template (e.g. `article.tmpl.php`). Enter
`incl()`, which lets you include the rendered contents of one template into
another. We can create a template called `series-nav.tmpl.php` with the
following.

```php
<?php namespace MattFerris\Staccato ?>
<nav>
  <ul>
    <li><a href="<?= $prev ?>">Previous</a></li>
    <li><a href="<?= $next ?>">Next</a></li>
  </ul>
</nav>
```

Now we can include this template from within `article.tmpl.php`.

```php
<?php begin($_, 'content') ?>
  <?= incl($_, 'series-nav.tpl.php', [ 'prev' => '...', 'next' => '...' ]) ?>
  <header><h1><?= $title ?></h1></header>
  <?= $body ?>
<?php end($_) ?>`
```

Note that `incl()` returns the contents of the template as a string, and
therefore must be output manually.

Caching
-------

While templates provide native support for caching, you can't take advantage
of this feature until you enable the *Cache* plugin.

```php
use MattFerris\Staccato\Staccato;
use MattFerris\Staccato\Plugins\Cache\FileCache;
use MattFerris\Staccato\Plugins\Cache\CachePlugin;

$path = 'cache/dir'; // path to a directory to store cache entries
$ttl = 3600; // keep cache entries for one hour
$cache = new FileCache('path/to/cache/dir', $ttl);

$staccato = (new Staccato())
    ->addPlugin(new CachePlugin($cache));
```

Once enabled, templates will automatically have their rendered contents cached.
All subsequent requests for the template will return the cached template until
either the cache entry expires, or the entry is deleted.

Caching can be disabled for a template using `set()`.

```php
<?php namespace MattFerris\Staccato ?>
<?php set($_, 'cachemode', 'disabled') ?>
```

`cachemode` can be `static` (default), `dynamic` (see below), or `disabled`.

### Cached blocks

The *cache* block will cache it's contents when the template is rendered. Even
if the template is re-rendered, it will use the cached block until it expires
or is deleted. This is most useful for content that is computationally expensive
to render.

```php
<?php cache($_, 'myblock', $ttl) ?>
  <?= some_expensive_operation() ?>
<?php end($_) ?>
```

`cache` accepts a third parameter which specifies how long (in seconds) the
cached data remains valid for. To maximize the benefit of a cache block, the
TTL should be set to greater value then the TTL of the template. TTLs default
to 1 hour (3,600 seconds), whereas a cache block could be set to 1 day (86,400
seconds), or even 1 week (604,800 seconds).

Note that a cached block can be extended using `begin()`, `append()`, and
`prepend()`. However, it's not possible to use `cache()` to extend a standard
block. Of course, any blocks that extend a cache block will have the contents
of the extended blocks cached.

`cache()` will always cache the contents of the block, regardless of what cache
mode is set for the template.

### Cached includes 

Templates can be included using `cincl()`, which will return the cached contents
of the included template. Like with `cache()`, `cincl()` also accepts a TTL as
a third parameter. And as with `cache()`, `cincl()` provides another option for
caching templates that are expensive to render.

```php
<?= cincl($_, 'template.tmpl.php', $ttl) ?>
```

`cincl()` will always cache the contents of the included template, regardless of
what cache mode is set for the current template and the included template.

### Caching fetched content

Fetching content from a remote URL can be expensive and/or time-consuming. In
cases where the remote content doesn't change often, you can use `cfetch()` to
cache the fetched content. `cfetch()` uses `file_get_contents()` to perform the
request, so you can use any protocols that it supports.

```php
<?= cfetch($_, 'https://example.com/expensive/api/call', $ttl) ?>
```

### Non-caching fetch

`ncfetch()` let's you incorporate dynamically fetched content in a cached
template. Using `ncfetch()` automatically sets the cache mode of the template
to `dynamic`. Templates using dynamic caching are actually compiled. A compiled
template incorporates *tags* that instruct the parser on how to fetch the remote
content. The remote content replaces the tags, and the result is returned. While
dynamically cached templates still undergo a small amount of processing, the
tradeoff makes it possible to maximize the utility of your templates.

```php
<?= ncfetch($_, 'https://example.com/feed') ?>
```

Markdown
========

Markdown functionality is available via the *Markdown* plugin. The plugin
introduces a *markdown* block which parses it's contents as markdown when the
template is rendered, and an `md()` function that parses a string as markdown.

Add the plugin.

```php
use MattFerris\Staccato\Staccato;
use MattFerris\Staccato\Plugins\Markdown\MarkdownPlugin;

$staccato = (new Staccato())
    ->addPlugin(new MarkdownPlugin());
```

And then start parsing markdown in your templates.

```php
<?php namespace MattFerris\Staccato ?>

<?php markdown($_, 'body') ?>
Article Title
=============

This is an *article* about *stuff*!
<?php end($_) ?>

<?= md($_, 'You shall not *pass*!') ?>
```
