<?php

/**
 * Staccato - A minimialist template library for native PHP templates
 * www.bueller.ca/staccato
 *
 * Plugins/Cache/FileCache.php
 * @copyright Copyright (c) 2023 Matt Ferris
 * @author Matt Ferris <matt@bueller.ca>
 *
 * Licensed under BSD 2-clause license
 * www.bueller.ca/staccato/license
 */

namespace MattFerris\Staccato\Plugins\Cache;

use DateTime;
use MattFerris\Staccato\CacheInterface;


/**
 * Use the filesystem to cache rendered and compiled templates
 */
class FileCache implements CacheInterface
{
    protected $path;


    /**
     * @param string $path Path to the cache folder
     */
    public function __construct(string $path) {
        $this->path = $path;
    }


    /**
     * Determine if an date is in the past
     *
     * @param DateTime $date The datetime to check
     * @return bool True if the datetime is in the past, otherwise false
     */
    protected function isExpired(DateTime $date): bool {
        $now = new DateTime();
        $diff = $date->diff($now);
        if ($diff->invert === 0) return true;
        return false;
    }


    /**
     * Check if the cache has a fresh entry for the given ID. This method will
     * return false if the cache has an entry for the ID, but the entry is
     * stale (has expired).
     *
     * @param string $id The entry ID to check
     * @return bool True if there is a fresh entry, otherwise false
     */
    public function has(string $id): bool {
        $file = $this->path.DIRECTORY_SEPARATOR.$id;
        if (!file_exists($file)) return false;

        $fh = fopen($file, 'r');
        flock($fh, LOCK_SH);
        $date = fread($fh, 25);
        flock($fh, LOCK_UN);
        fclose($fh);

        if ($this->isExpired(new DateTime($date))) return false;

        return true;
    }


    /**
     * Get a cache entry
     *
     * @param string $id The entry ID to get
     * @return string The cache contents
     */
    public function get(string $id): string {
        if (!$this->has($id)) {
            throw new CacheIdNotFoundException($id);
        }
     
        $file = $this->path.DIRECTORY_SEPARATOR.$id;

        $fh = fopen($file, 'r');
        flock($fh, LOCK_SH);
        $date = fread($fh, 25);

        if ($this->isExpired(new DateTime($date))) {
             flock($fh, LOCK_UN);
             fclose($fh);
             throw new CacheExpiredException($id);
        }

        $csum = fread($fh, 8);

        $contents = '';
        while (!feof($fh)) {
            $contents .= fread($fh, 4096);
        }

        flock($fh, LOCK_UN);
        fclose($fh);

        if (dechex(crc32($contents)) !== $csum) {
            throw new CacheValidationException($id);
        }

        return $contents;
    }


    /**
     * Cache content under a given ID, optionally setting a TTL for the content
     *
     * @param string $id The cache ID
     * @param string $contents The content to cache
     * @param int $ttl The number of seconds until the entry expires
     */
    public function put(string $id, string $contents, int $ttl = 3600) {
        $file = $this->path.DIRECTORY_SEPARATOR.$id;

        $date = (new DateTime("+$ttl seconds"))->format(DATE_ATOM);
        $csum = dechex(crc32($contents));

        file_put_contents("{$file}.tmp", $date.$csum.$contents);
        rename("{$file}.tmp", $file);
    }
}

