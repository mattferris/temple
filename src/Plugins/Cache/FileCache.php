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
use RuntimeException;


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

        if (!file_exists($this->path) || !is_dir($this->path)) {
            throw new RuntimeException('nonexistent cache folder: '.$this->path);
        }
    }


    /**
     * Generate the cache filename for a given ID
     *
     * @param string $id The entry ID
     * @return string
     */
    protected function file(string $id): string {
        $sum = hash('sha256', $id);
        return $this->path.DIRECTORY_SEPARATOR.substr($sum, 0, 2).DIRECTORY_SEPARATOR.$sum;
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
        $file = $this->file($id);
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
     
        $file = $this->file($id);

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
        if ($id === '') {
            throw new RuntimeException('empty cache ID');
        }

        $file = $this->file($id);

        $date = (new DateTime("+$ttl seconds"))->format(DATE_ATOM);
        $csum = sprintf('%0.8s', dechex(crc32($contents)));

        file_put_contents("{$file}.tmp", $date.$csum.$contents);
        rename("{$file}.tmp", $file);
    }


    /**
     * Clear a signle cache entry
     *
     * @param string $id
     */
    public function del(string $id) {
        $file = $this->file($id);
        if (file_exists($file)) {
            unlink($file);
        }
    }


    /**
     * Clear all the cache entries
     */
    public function clear() {
        $paths = [$this->path];
        while ($path = array_shift($paths)) {

            $dir = opendir($this->path);
            while ($file = readdir($this->path)) {

                if (strpos($file, '.') === 0) continue;

                if (is_dir($file)) {
                    array_unshift($paths, $file);
                    continue;
                }

                unlink($file);

            }
            closedir($dir);

        }
    }
}

