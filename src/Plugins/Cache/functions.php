<?php

/**
 * Staccato - A minimialist template library for native PHP templates
 * www.bueller.ca/staccato
 *
 * Plugins/Cache/functions.php
 * @copyright Copyright (c) 2023 Matt Ferris
 * @author Matt Ferris <matt@bueller.ca>
 *
 * Licensed under BSD 2-clause license
 * www.bueller.ca/staccato/license
 */

namespace MattFerris\Staccato;

use MattFerris\Staccato\Plugins\Cache\CacheBlock;


/**
 * Check if the template has a cache instance
 *
 * @param Template $template The template instance
 */
function _checkCache(Template $template): CacheInterface {
    $cache = $template->getCache();
    $path = $template->getPath();
    if (is_null($template->getCache())) {
        throw new RuntimeException("template '{$path}' does not have a cache instance");
    }
    return $cache;
}


/**
 * Create an independently cacheable block
 *
 * @param Template $template The template the block is defined in
 * @param string $name The name of the block
 * @param int $ttl The number of seconds until the entry expires
 */
function cache(Template $template, string $name, int $ttl = 3600) {
    _checkCache($template);
    $parent = $template->getCurrentBlock();
    $template->addBlock(new CacheBlock($template, $name, $parent), $name);
}


/**
 * Include the cached contents of a template
 *
 * @param Template $template The template instance
 * @param string $name The name of the template to include
 * @param array $vars Variables to define to the included template
 * @param int $ttl The number of seconds until the cached template expires
 * @return string The template contents
 */
function cincl(Template $template, string $name, array $vars = [], int $ttl = 3600): string {
    $cache = _checkCache($template);
    $inclTmpl = $template->newTemplate($name, $vars);
    $output = '';

    $id = $inclTmpl->getId();
    if ($cache->has($id)) {
        $output = $cache->get($id);
    } else {
        $output = $inclTmpl->render();
        $cache->put($id, $output, $ttl);
    }

    return $output;
}


/**
 * Cache content from a remote resource
 *
 * @param Template $template The template the block is defined in
 * @param string $url The URL to fetch the content from
 * @param int $ttl The number of seconds until the entry expires
 * @return string
 */
function cfetch(Template $template, string $url, int $ttl = 3600): string {
    $cache = _checkCache($template);
    $id = sha1($template->getId().$url);

    $content = '';
    if (!$cache->has($id)) {
        $content = file_get_contents($url);
        if ($content === false) return false;
        $cache->put($id, $content, $ttl);
    } else {
        $content = $cache->get($id);
    }

    return $content;
}


/**
 * Non-caching (i.e. "nc") content fetch. Puts template into 'dynamic' cache
 * mode.
 *
 * @param Template $template The template the block is defined in
 * @param string $url The URL to fetch the content from
 * @return string
 */
function ncfetch(Template $template, string $url): string {
    $cache = _checkCache($template);
    $template->setCacheMode('dynamic');
    $varstr = base64_encode(serialize(['file_get_contents', $url]));
    return "{%{$varstr}%}"; 
}
