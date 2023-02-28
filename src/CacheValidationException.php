<?php

/**
 * Staccato - A minimialist template library for native PHP templates
 * www.bueller.ca/staccato
 *
 * CacheValidationException.php
 * @copyright Copyright (c) 2023 Matt Ferris
 * @author Matt Ferris <matt@bueller.ca>
 *
 * Licensed under BSD 2-clause license
 * www.bueller.ca/staccato/license
 */

namespace MattFerris\Staccato;

use Exception;


class CacheValidationException extends Exception
{
    protected $cacheId;


    /**
     * @param string $cacheId The ID of the cache entry
     */
    public function __construct(string $cacheId) {
        $this->cacheId = $cacheId;
        parent::__construct("unable to validate cached data for '{$cacheId}'");
    }


    /**
     * @return string The ID of the cache entry
     */
    public function getCacheId(): string {
        return $this->cacheId;
    }
}

