<?php

/**
 * Staccato - A minimialist template library for native PHP templates
 * www.bueller.ca/staccato
 *
 * Plugins/Markdown/MarkdownPlugin.php
 * @copyright Copyright (c) 2023 Matt Ferris
 * @author Matt Ferris <matt@bueller.ca>
 *
 * Licensed under BSD 2-clause license
 * www.bueller.ca/staccato/license
 */

namespace MattFerris\Staccato\Plugins\Markdown;

use MattFerris\Staccato\PluginInterface;
use MattFerris\Staccato\Staccato;


class MarkdownPlugin implements PluginInterface
{
    /**
     * Initialize the plugin
     *
     * @param Staccato $staccato The Staccato instance
     */
    public function init(Staccato $staccato) {
        require(__DIR__.DIRECTORY_SEPARATOR.'functions.php');
    }
}

