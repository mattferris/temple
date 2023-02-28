<?php

/**
 * Staccato - A minimialist template library for native PHP templates
 * www.bueller.ca/staccato
 *
 * BlockMismatchException.php
 * @copyright Copyright (c) 2023 Matt Ferris
 * @author Matt Ferris <matt@bueller.ca>
 *
 * Licensed under BSD 2-clause license
 * www.bueller.ca/staccato/license
 */

namespace MattFerris\Staccato;

use Exception;


class BlockMismatchException extends Exception
{
    protected $template;
    protected $parentBlock;
    protected $childBlock;


    public function __construct(Template $template, BlockInterface $parent, BlockInterface $child) {
        $this->template = $template;
        $this->parentBlock = $parent;
        $this->childBlock = $child;

        $parentClass = get_class($parent);
        $childClass = get_class($child);

        parent::__construct(
            "unable to extend parent block '{$parent->getName()}' ({$parentClass}) ".
            "as child is not the same type ({$childClass}) ".
            "in template '{$template->getPath()}'"
        ); 
    }


    public function getTemplate(): Template {
        return $this->template;
    }


    public function getParentBlock(): BlockInterface {
        return $this->parentBlock;
    }


    public function getChildBlock(): BlockInterface {
        return $this->childBlock;
    }
}

