<?php

/**
 * Staccato - A minimialist template library for native PHP templates
 * www.bueller.ca/staccato
 *
 * CacheIdNotFoundException.php
 * @copyright Copyright (c) 2023 Matt Ferris
 * @author Matt Ferris <matt@bueller.ca>
 *
 * Licensed under BSD 2-clause license
 * www.bueller.ca/staccato/license
 */


namespace MattFerris\Staccato;

use Exception;


class CacheIdNotFoundException extends Exception
{
    protected $cacheId;


    /**
     * @param string $cacheId The ID of the cache entry
     */
    public function __construct(string $cacheId) {
        $this->cacheId = $cacheId;
        parent::__construct("no cache entry found for '{$id}'");
    }


    /**
     * @return string The ID of the cache entry
     */
    public function getCacheId(): string {
        return $this->cacheId;
    }
}

