<?php

/**
 * Staccato - A minimialist template library for native PHP templates
 * www.bueller.ca/staccato
 *
 * Block.php
 * @copyright Copyright (c) 2023 Matt Ferris
 * @author Matt Ferris <matt@bueller.ca>
 *
 * Licensed under BSD 2-clause license
 * www.bueller.ca/staccato/license
 */

namespace MattFerris\Staccato; 

/**
 * A representation of a template block. Blocks are the means by which a
 * template is extended. Extending templates can manipulate the previously
 * defined blocks, even choosing to include new blocks within the existing
 * once.
 */
class Block implements BlockInterface
{
    const MODE_REPLACE = 0;
    const MODE_PREPEND = 1;
    const MODE_APPEND = 2;

    protected $template;
    protected $parent;
    protected $id;
    protected $name;
    protected $contents = '';
    protected $children = [];
    protected $extended = false;
    protected $mode = self::MODE_REPLACE;


    /**
     * @param Template $template The template the block is defined in
     * @param string $name The name of the block
     * @param BlockInterface $parent The parent block
     */
    public function __construct(Template $template, string $name, BlockInterface $parent = null) {
        $this->template = $template;
        $this->parent = $parent;
        $this->id = sha1($template->getId().$name);
        $this->name = $name;
    }


    /**
     * Set the parent block
     *
     * @param BlockInterface $parent The parent block
     */
    public function setParent(BlockInterface $parent) {
        $this->parent = $parent;
    }


    /**
     * Create a child block
     *
     * @param string $name The name of the child block
     * @return BlockInterface The new child block
     */
    public function createChild(string $name): BlockInterface {
        $child = new self($this->template, $name, $this);
        $this->addChild($child);
        return $child;
    }


    /**
     * Add a child block
     *
     * @param BlockInterface $block The block to add a child
     */
    public function addChild(BlockInterface $block) {
        $this->children[] = $block;
    }


    /**
     * Return the block ID
     *
     * @return string The block ID
     */
    public function getId(): string {
        return $this->id;
    }


    /**
     * Return block name
     *
     * @return string The block name
     */
    public function getName(): string {
        return $this->name;
    }


    /**
     * Set wether the block is extended or not
     *
     * @param bool $extended True if extended, otherwise false
     */
    public function setExtended(bool $extended) {
        $this->extended = $extended;
    }


    /**
     * Return wether the block is extended or not
     *
     * @return bool True if the block is extended, otherwise false
     */
    public function isExtended(): bool {
        return $this->extended;
    }


    /**
     * Set the content mode of this block
     *
     * @param int $mode The content mode of the block
     */
    public function setMode(int $mode) {
        $this->mode = $mode;
    }


    /**
     * Set the contents of this block
     *
     * @param string $contents The contents of this block
     */
    public function setContents(string $contents) {
        switch ($this->mode) {
            case self::MODE_PREPEND:
                $this->contents = $contents.$this->contents;
                break;
            case self::MODE_REPLACE:
                $this->contents = $contents;
                break;
            case self::MODE_APPEND:
                $this->contents .= $contents;
                break;
        }
    }


    /**
     * Render the block
     *
     * @return string The rendered block
     */
    public function render(): string {
        $search = [];
        $replace = [];

        foreach ($this->children as $child) {
            $search[] = '{{'.$child->getId().'}}';
            $replace[] = $child->render();
        }

        return str_replace($search, $replace, $this->contents);
    } 
}

