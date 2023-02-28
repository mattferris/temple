<?php

/**
 * Staccato - A minimialist template library for native PHP templates
 * www.bueller.ca/staccato
 *
 * TemplateNotFoundException.php
 * @copyright Copyright (c) 2023 Matt Ferris
 * @author Matt Ferris <matt@bueller.ca>
 *
 * Licensed under BSD 2-clause license
 * www.bueller.ca/staccato/license
 */

namespace MattFerris\Staccato;

use Exception;


class TemplateNotFoundException extends Exception
{
    protected $templateName;


    /**
     * @param string $templateName The template name
     */
    public function __construct(string $templateName) {
        $this->templateName = $templateName;
        parent::__construct("template not found '{$templateName}'");
    }


    /**
     * Return the template namte
     *
     * @return string The template name
     */
    public function getTemplateName(): string {
        return $this->templateName;
    }
}

