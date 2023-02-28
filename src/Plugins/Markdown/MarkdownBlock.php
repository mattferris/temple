<?php

/**
 * Staccato - A minimialist template library for native PHP templates
 * www.bueller.ca/staccato
 *
 * Plugins/Markdown/MarkdownBlock.php
 * @copyright Copyright (c) 2023 Matt Ferris
 * @author Matt Ferris <matt@bueller.ca>
 *
 * Licensed under BSD 2-clause license
 * www.bueller.ca/staccato/license
 */

namespace MattFerris\Staccato\Plugins\Markdown;

use MattFerris\Staccato\Block;


class MarkdownBlock extends Block
{
    public function render(): string {
        $this->contents = (new Parsedown())->text($this->contents);
        return parent::render();
    }
}

