<?php

/**
 * Staccato - A minimialist template library for native PHP templates
 * www.bueller.ca/staccato
 *
 * CacheInterface.php
 * @copyright Copyright (c) 2023 Matt Ferris
 * @author Matt Ferris <matt@bueller.ca>
 *
 * Licensed under BSD 2-clause license
 * www.bueller.ca/staccato/license
 */

namespace MattFerris\Staccato;


interface CacheInterface
{
    public function has(string $id): bool;
    public function get(string $id): string;
    public function put(string $id, string $contents, int $ttl = 3600);
}
