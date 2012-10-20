<?php

namespace bolt\render;
use \b as b;

b::render()->plug('cli', '\bolt\render\cli');

// json
class cli extends \bolt\plugin\singleton {

    // accept or header
    public static $accept = array();
    
    // content type
    public $contentType = false;
    
    //
    public function render($view) {
        
        // give it up
        return $view->getContent();
    
    }

}