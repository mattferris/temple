<?php

/**
 * Staccato - A minimialist template library for native PHP templates
 * www.bueller.ca/staccato
 *
 * BlockInterface.php
 * @copyright Copyright (c) 2023 Matt Ferris
 * @author Matt Ferris <matt@bueller.ca>
 *
 * Licensed under BSD 2-clause license
 * www.bueller.ca/staccato/license
 */

namespace MattFerris\Staccato;


interface BlockInterface
{
    public function getId();
    public function setParent(BlockInterface $parent);
    public function setContents(string $contents);
    public function setMode(int $mode);
    public function setExtended(bool $extended);
    public function isExtended(): bool;
    public function createChild(string $name): BlockInterface;
    public function addChild(BlockInterface $block);
    public function render(): string;
}

