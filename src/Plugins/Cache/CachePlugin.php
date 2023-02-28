<?php

/**
 * Staccato - A minimialist template library for native PHP templates
 * www.bueller.ca/staccato
 *
 * Plugins/Cache/CachePlugin.php
 * @copyright Copyright (c) 2023 Matt Ferris
 * @author Matt Ferris <matt@bueller.ca>
 *
 * Licensed under BSD 2-clause license
 * www.bueller.ca/staccato/license
 */

namespace MattFerris\Staccato\Plugins\Cache;

use MattFerris\Staccato\CacheInterface;
use MattFerris\Staccato\PluginInterface;
use MattFerris\Staccato\Staccato;


class CachePlugin implements PluginInterface
{
    protected $cache;
    protected $ttl = 3600;


    /**
     * @param CacheInterface $cache The cache instance
     * @param int $ttl The number of seconds until the cache entries expire
     */
    public function __construct(CacheInterface $cache, int $ttl = 3600) {
        $this->cache = $cache;
        $this->ttl = $ttl;
    }


    /**
     * Initialize the plugin
     *
     * @param Staccato $staccato The Staccato instance
     */
    public function init(Staccato $staccato) {
        require(__DIR__.DIRECTORY_SEPARATOR.'functions.php');
        $staccato->setCache($this->cache, $this->ttl);
    }
}

