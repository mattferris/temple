<?php

/**
 * Staccato - A minimialist template library for native PHP templates
 * www.bueller.ca/staccato
 *
 * functions.php
 * @copyright Copyright (c) 2023 Matt Ferris
 * @author Matt Ferris <matt@bueller.ca>
 *
 * Licensed under BSD 2-clause license
 * www.bueller.ca/staccato/license
 */

namespace MattFerris\Staccato;

use InvalidArgumentException;

const ESC_HTML = 'html';
const ESC_URI = 'uri';


/**
 * Open a new block in append mode.
 *
 * @param Template $template The current template
 * @param string $name The name of the block
 */
function append(Template $template, string $name) {
    $template->append($name);
}


/**
 * Open a new block in regular (replace) mode.
 *
 * @param Template $template The current template
 * @param string $name The name of the block
 */
function begin(Template $template, string $name) {
    $template->begin($name);
}


/**
 * Return the content from another block
 * 
 * @param Template $template The current template
 * @param string $name The name of the block
 * @return ?string The contentes of the specified block
 */
function content(Template $template, string $name): ?string {
    $block = $template->getBlock($name);
    return is_null($block) ? null : $block->render();
}


/**
 * Dump the contents of a template variable.
 *
 * @param mixed $var The variable to dump
 * @return string The variable contents
 */
function dump($var): string {
    ob_start();
    var_dump($var);
    $dump = ob_get_contents();
    ob_end_clean();
    return $dump;
}


/**
 * Escape a string.
 *
 * @param string $value The string to escape
 * @param string $hint Optional hint for how to escape (defaults to using htmlspecialchars())
 * @return string The escaped string
 */
function e(string $value, string $hint = ESC_HTML): string {
    switch ($hint) {
        case ESC_HTML: return htmlspecialchars($value);
        case ESC_URI: return urlencode($value);
    }
}


/**
 * Close the currently open block.
 *
 * @param Template The current template
 */
function end(Template $template) {
    $template->end();
}


/**
 * Extend a template.
 *
 * @param Template $template The current template
 * @param string $name The name of the template to extend
 */
function extend(Template $template, string $name) {
    $template->extend($name);
}


/**
 * Import blocks from another template. Imported blocks are stored in the
 * namespace $as, and are referenced using content().
 *
 * @param Template $template The current template
 * @param string $name The name of the template to import
 * @param string $as The namespace for the imported blocks
 */
function import(Template $template, string $name, string $as) {
    $template->import($name, $as);
}


function macro(Template $template, string $name): callable {
    return function (array $vars = []) use ($template, $name) {
        return incl($template, $name, $vars);
    };
}


/**
 * Output the contents of the parent block (if extended)
 *
 * @param Template $template The current template
 * @return string The contents of the parent block
 */
function parent(Template $template): string {
    return $template->getCurrentBlock()->render();
}


/**
 * Open a new block in prepend mode.
 *
 * @param Template $template The current template
 * @param string $name The name of the block
 */
function prepend(Template $template, string $name) {
    $template->prepend($name);
}


/**
 * Include the contents of another template. Optionally provide a custom list of
 * variables for the template.
 *
 * @param Template $template The current template
 * @param string $name The name of the template to fetch
 * @param array $vars An optional list of variables for the fetched template
 * @return string The template contents
 */
function incl(Template $template, string $name, array $vars = []): string {
    return $template->include($name, $vars);
}


/**
 * Set template options
 *
 * Valid options are:
 *
 * - cachemode { "static" | "dynamic" | "disabled" }
 *
 * @param Template $template The current template
 * @param string $option The template option
 * @param mixed $value The value of the option
 */
function opt(Template $template, string $option, $value) {
    switch ($option) {
        case 'cachemode':
            break;

        default:
            throw new InvalidArgumentException("invalid option '{$option}'");
    }
    $method = "set{$option}";
    $template->$method($value);
}


/**
 * Alias of opt(); deprecated
 */
function set(Template $template, string $option, $value) {
    trigger_error("set() is deprecated and will be removed in a future release, use opt() instead", E_USER_NOTICE);
    return opt($template, $option, $value);
}


/**
 * Define a variable within the template. Required if you want to define
 * variables for use in extended templates.
 *
 * @param Template $template The current template
 * @param string $name The variable name
 * @param mixed $value The value of the option
 */
function let(Template $template, string $name, $value) {
    $template->setVariable($name, $value);
}

