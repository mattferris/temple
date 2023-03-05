<?php

/**
 * Staccato - A minimialist template library for native PHP templates
 * www.bueller.ca/staccato
 *
 * Template.php
 * @copyright Copyright (c) 2023 Matt Ferris
 * @author Matt Ferris <matt@bueller.ca>
 *
 * Licensed under BSD 2-clause license
 * www.bueller.ca/staccato/license
 */

namespace MattFerris\Staccato;

use RuntimeException;


/**
 * Templates represent the template file
 */
class Template
{
    protected $id;
    protected $path;
    protected $resolver;
    protected $vars = [];
    protected $root;
    protected $blocks = [];
    protected $stack = [];
    protected $cache;
    protected $cacheMode = 'static';
    protected $extends;


    /**
     * @param string $path The path to the template file
     * @param array $vars Variables for the template
     * @param callable $resolver A callback to resolve template names
     * @param CacheInterface $cache An optional cache instance
     */
    public function __construct(
        string $path,
        array $vars = [],
        callable $resolver = null,
        CacheInterface $cache = null
    ) {
        if (is_null($resolver)) {
            $resolver = function ($path) { return $path; };
        }

        $this->id = sha1($path.serialize($vars));
        $this->path = $path;
        $this->vars = $vars;
        $this->cache = $cache;
        $this->resolver = $resolver;

        // instantiate root block
        $root = new Block($this, '.');
        $this->blocks['.'] = $this->root = $root;
        $this->stack[] = $root;
    }


    /**
     * Parse a compiled template 
     *
     * @param string $path The path to the template file
     * @param array $vars Variables for the template
     * @param callable $resolver A callback to resolve template names
     */
    public static function renderFromString(string $contents, array $vars = [], callable $resolver = null): string {
        $out = '';
        $cur = $last = $end = 0;
        $len = strlen($contents);
        while ($cur = strpos($contents, '{%', $cur)) {
            $end = strpos($contents, '%}', $cur);
            $tag = substr($contents, $cur+2, $end-$cur-2);
            $out .= substr($contents, $last, $cur-$last);
            $def = unserialize(base64_decode($tag));
            if (!is_array($def) || count($def) === 0) {
                throw new RuntimeException('invalid compilation tag');
            }
            $fn = array_shift($def);
            $out .= call_user_func_array($fn, $def);
            $last = $cur;
            $cur = $end + 2;
        }
        $out .= substr($contents, $end + 2);
        return $out;
    }


    /**
     * Return the template ID
     *
     * @return string The template ID
     */
    public function getId(): string {
        return $this->id;
    }


    /**
     * Return the template path
     *
     * @return string The template path
     */
    public function getPath(): string {
        return $this->path;
    }


    /**
     * Return the blocks for this template
     *
     * @return BlockInterface[] The blocks for this template
     */
    public function getBlocks(): array {
        return $this->blocks;
    }


    /**
     * Return the cache instance
     *
     * @return CacheInterface|null The cache instance
     */
    public function getCache(): ?CacheInterface {
        return $this->cache;
    }


    /**
     * Set the caching mode. Possible modes are:
     *
     * static
     * dynamic
     * disabled
     *
     * @param string $mode Set the cache mode for the template
     */
    public function setCacheMode(string $mode) {
        switch ($mode) {
            case 'static':
            case 'dynamic':
            case 'disabled':
                $this->cacheMode = $mode;
                break;

            default:
                throw new InvalidArgumentException(
                    "cache mode must be one of 'static', 'dynamic', or 'disabled'"
                );
        }
    }


    /**
     * Return the caching mode
     *
     * @return string The caching mode
     */
    public function getCacheMode(): string {
        return $this->cacheMode;
    }


    /**
     * Return the current block
     *
     * @return BlockInterface The current block for this template
     */
    public function getCurrentBlock(): BlockInterface {
        return $this->stack[count($this->stack)-1];
    }


    /**
     * Return the currently defined variables for the template
     *
     * @return array The defined variables
     */
    public function getVariables(): array {
        return $this->vars;
    }


    /**
     * Define a variable for the template
     *
     * @param string $name The variable name
     * @param mixed $value The variable value
     */
    public function setVariable(string $name, $value) {
        $this->vars[$name] = $value;
    }


    /**
     * Return a new template instance
     *
     * @param string $name Path to template
     * @param array $vars Template variables
     */
    public function newTemplate(string $name, array $vars = []): Template {
        $path = ($this->resolver)($name, [ dirname($this->path) ]);
        return new self($path, array_merge($this->vars, $vars), $this->resolver, $this->cache);
    }


    /**
     * Extend an existing template. Called from within the output buffer of the
     * extending template.
     *
     * @param string $name The name of the template to extend
     */
    public function extend(string $name) {
        $template = $this->newTemplate($name);
        $template->setCacheMode($this->cacheMode);
        $template->prepare();
        $this->vars = array_merge($template->getVariables(), $this->vars);
        $this->blocks = $template->getBlocks();

        $this->root = $root = $this->blocks['.'];
        $this->stack = [ $root ];

        $root->setExtended(true);
        $this->extends = $name;

    }


    /**
     * Really fetch the contents of a rendered template.
     *
     * @param string $name The name of the template
     * @param array $vars Variables to define for the fetched template
     * @return The rendered template
     */
    public function include(string $name, array $vars = []): string {
        return $this->newTemplate($name, $vars)->render();
    }


    /**
     * Create a new block. Called from within the output buffer of the template.
     *
     * @param string $name The block name
     * @return BlockInterface The new block
     */ 
    protected function block(string $name): BlockInterface {
        $block = null;
        if (isset($this->blocks[$name])) {
            $block = $this->blocks[$name];
            $block->setExtended(true);
        } else {
            $parent = $this->stack[count($this->stack)-1];
            $block = $parent->createChild($name);
            $this->blocks[$name] = $block;
        }
        $this->stack[] = $block;

        ob_start();
        return $block;
    }


    /**
     * Add a new block. Called from within the output buffer of the template.
     *
     * @param BlockInterface $block The block to add
     * @param string $name The block name
     */
    public function addBlock(BlockInterface $block, string $name) {
        if (isset($this->blocks[$name])) {
            if (get_class($this->blocks[$name]) !== get_class($block)) {
                throw new BlockMismatchException($this, $this->blocks[$name], $block);
            }
            $block = $this->blocks[$name];
            $block->setExtended(true);
        } else {
            $parent = $this->stack[count($this->stack)-1];
            $parent->addChild($block);
            $this->blocks[$name] = $block;
        }
        $this->stack[] = $block;
        ob_start();
    } 


    /**
     * Begin a new block. Called from within the output buffer of the template.
     *
     * @param string $name The block name
     */ 
    public function begin(string $name) {
        ($this->block($name))->setMode(Block::MODE_REPLACE);
    }


    /**
     * Begin a new block in prepend mode. Called from within the output buffer
     * of the template.
     *
     * @param string $name The block name
     */ 
    public function prepend(string $name) {
        ($this->block($name))->setMode(Block::MODE_PREPEND);
    }


    /**
     * Begin a new block in append mode. Called from within the output buffer
     * of the template.
     *
     * @param string $name The block name
     */ 
    public function append(string $name) {
        ($this->block($name))->setMode(Block::MODE_APPEND);
    }


    /**
     * End the currently open block. Called from within the output buffer of 
     * the template.
     */
    public function end() {
        $block = array_pop($this->stack);
        $block->setContents(ob_get_contents());
        ob_end_clean();
        if ($block->isExtended()) return;
        echo '{{'.$block->getId().'}}';
    }


    /**
     * Prepare the content of the blocks by parsing the template and capturing
     * the resulting output. No output is generated.
     */
    public function prepare() {
        ob_start();

        (function ($_) {
            extract($this->vars);
            include($this->path);
        })($this);

        $contents = ob_get_contents();
        ob_end_clean();

        if ($this->root->isExtended()) return;

        $this->root->setContents($contents);
    }


    /**
     * Render the template. Unless this is an extended template, prepare the
     * block content. Finally, call Block::render() on the root block to
     * render the whole block tree.
     */ 
    public function render(): string {
        $this->prepare();
        return $this->root->render();
    }
}

