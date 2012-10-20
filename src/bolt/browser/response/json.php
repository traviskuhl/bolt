<?php

namespace bolt\render;
use \b as b;

b::render()->plug('json', '\bolt\render\json');

// json
class json extends \bolt\plugin\singleton {

    // accept or header
    public static $accept = array(
        100 => 'text/javascript'
    );
    
    // content type
    public $contentType = "text/javascript";
    
    //
    public function render($view) {
        
        // give it up
        return json_encode(array(
            'status' => $view->getStatus(),
            'response' => $view->getData()
        ));
    
    }

}