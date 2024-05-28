<?php

/**
 * Staccato - A minimialist template library for native PHP templates
 * www.bueller.ca/staccato
 *
 * Plugins/Markdown/functions.php
 * @copyright Copyright (c) 2023 Matt Ferris
 * @author Matt Ferris <matt@bueller.ca>
 *
 * Licensed under BSD 2-clause license
 * www.bueller.ca/staccato/license
 */

namespace MattFerris\Staccato;

use MattFerris\Staccato\Plugins\Markdown\MarkdownBlock;
use MattFerris\Staccato\Plugins\Markdown\Parsedown;


/**
 * Create a markdown block
 * 
 * @param Template $template The current template
 * @param string $name The name of the block
 */
function markdown(Template $template, string $name) {
    $block = new MarkdownBlock($template, $name);
    $template->addBlock($block, $name, $cache);
}


/**
 * Parse a string as markdown
 *
 * @param Template $template The current template
 * @param string $md The string to parse
 * @return string The parsed markdown
 */
function md(Template $template, string $md): string {
    return (new ParseDown())->text($md);
}
