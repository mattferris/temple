<?php

/**
 * Staccato - A minimialist template library for native PHP templates
 * www.bueller.ca/staccato
 *
 * Staccato.php
 * @copyright Copyright (c) 2023 Matt Ferris
 * @author Matt Ferris <matt@bueller.ca>
 *
 * Licensed under BSD 2-clause license
 * www.bueller.ca/staccato/license
 */

namespace MattFerris\Staccato;

require(__DIR__.'/functions.php');


/**
 * The entry point for the template system.
 */
class Staccato
{
    protected $cache;
    protected $ttl = 3600;
    protected $vars = [];
    protected $paths = [];
    protected $namespaces = [];


    /**
     * @param string[] $paths Default search paths for non-namespaced templates
     */
    public function __construct(array $paths = []) {
        $this->paths = $paths;
    }


    /**
     * Define a variable for all templates
     *
     * @param string $name The variable name
     * @param mixed $value The variable value
     * @return self
     */
    public function addGlobal(string $name, $value): self {
        $this->vars[$name] = $value;
        return $this;
    }


    /**
     * Define a template namespace. A namespace maps a symbol to a collection
     * of search paths.
     *
     * @param string $namespace The name of the namespace
     * @param string[] $paths An array of search paths
     * @return self
     */
    public function addNamespace(string $namespace, array $paths): self {
        $this->namespaces[$namespace] = $paths;
        return $this;
    }


    /**
     * Add a plugin to this instance
     *
     * @param PluginInterface $plugin The plugin to add
     * @return self
     */
    public function addPlugin(PluginInterface $plugin): self {
        $plugin->init($this);
        return $this;
    }


    /**
     * Set a cache implementation
     *
     * @param CacheInterface $cache The cache implementation
     * @param int $ttl The number of seconds until the cache entry expires
     */
    public function setCache(CacheInterface $cache, int $ttl = 3600): self {
        $this->cache = $cache;
        $this->ttl = $ttl;
        return $this;
    }


    /**
     * Resolve a template name to a file path
     *
     * @param string $name The template name to resolve
     * @param string[] $paths Optional list of additional search paths
     * @return string The resolved template path
     */
    protected function resolve(string $name, array $paths = []): string {
        $paths = array_merge($this->paths, $paths);

        if (strpos($name, ':') !== false) {
            $parts = explode(':', $name);
            $ns = $parts[0];
            if (count($parts) === 2 && isset($this->namespaces[$ns])) {
                $name = $parts[1];
                $paths = $this->namespaces[$ns];
            }
        }

        foreach ($paths as $path) {
            $file = $path.DIRECTORY_SEPARATOR.$name;
            if (file_exists($file)) {
                return $file;
            }
        }

        if (!file_exists($name)) {
            throw new TemplateNotFoundException($name);
        }

        return $name;
    }


    /**
     * Render a template
     *
     * @param string $name The name of the template to render
     * @param array $vars A list of variables to define for the template
     * @param string[] A list of custom search paths
     * @param bool $globals If true, use global vars in template
     * @return string The rendered template
     */
    public function render(string $name, array $vars = [], array $paths = [], bool $globals = true): string {
        $path = $this->resolve($name, $paths);

        if ($globals === true) {
            $vars = array_merge($this->vars, $vars);
        }

        $resolver = function ($path) { return $this->resolve($path); };
        $template = new Template($path, $vars, $resolver, $this->cache);

        if (!is_null($this->cache)) {
            $id = $template->getId();

            $compiledId = sha1($id.'compiled');
            if ($this->cache->has($compiledId)) {
                return Template::renderFromString($this->cache->get($compiledId) , $vars, $resolver);
            }
                     
            if ($this->cache->has($template->getId())) {
                return $this->cache->get($template->getId());
            }

            $result = $template->render();

            $cacheMode = $template->getCacheMode();
            if ($cacheMode === 'static') {
                $this->cache->put($id, $result, $this->ttl);
            } elseif ($cacheMode === 'dynamic') {
                $this->cache->put($compiledId, $result, $this->ttl);
                $result = Template::renderFromString($result, $vars, $resolver);
            }

            return $result;
        }

        return $template->render();
    }
}

