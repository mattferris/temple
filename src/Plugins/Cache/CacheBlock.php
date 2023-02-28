<?php

/**
 * Staccato - A minimialist template library for native PHP templates
 * www.bueller.ca/staccato
 *
 * Plugins/Cache/CacheBlock.php
 * @copyright Copyright (c) 2023 Matt Ferris
 * @author Matt Ferris <matt@bueller.ca>
 *
 * Licensed under BSD 2-clause license
 * www.bueller.ca/staccato/license
 */

namespace MattFerris\Staccato\Plugins\Cache;

use MattFerris\Staccato\Block;
use MattFerris\Staccato\BlockInterface;
use MattFerris\Staccato\Template;


class CacheBlock extends Block
{
    protected $ttl;


    /**
     * @param Template $template The template the block is defined in
     * @param string $name The name of the block
     * @param BlockInterface $parent The parent block
     * 
     */
    public function __construct(
        Template $template,
        string $name,
        BlockInterface $parent = null,
        int $ttl = 3600)
    {
        $this->ttl = $ttl;
        parent::__construct($template, $name, $parent);
    }


    /**
     * Render the block
     *
     * @return string The rendered block
     */
    public function render(): string {
        $cache = $this->template->getCache();
        if (is_null($cache)) return parent::render();

        if (!$cache->has($this->getId())) {
            $contents = parent::render();
            $cache->put($this->getId(), $contents, $this->ttl);
            return $contents;
        }

        return $cache->get($this->getId());
    }
}

