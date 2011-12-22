<?php

namespace bolt\render;
use \b as b;

b::render()->plug('html', '\bolt\render\html');

// json
class html extends \bolt\plugin\singleton {

    // accept or header
    public static $accept = array(
        100 => 'text/html'
    );
    
    // content type
    public $contentType = "text/html";
    
    //
    public function render($view) {
        
        // give it up
        return $view->getContent();
            
    }

}