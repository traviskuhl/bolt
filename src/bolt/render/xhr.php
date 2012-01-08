<?php

namespace bolt\render;
use \b as b;

b::render()->plug('xhr', '\bolt\render\xhr');

// json
class xhr extends \bolt\plugin\singleton {

    // accept or header
    public static $accept = array(
        100 => 'text/javascript;text/xhr'
    );
    
    // content type
    public $contentType = "text/javascript";
    
    //
    public function render($view) {
    
        // we need to ask the html renderer
        $h = b::render()->call('html');
        
        // give it up
        return json_encode(array(
            'status' => $view->getStatus(),
            'response' => array(
                'html' => $h->render($view),
                'data' => $view->getData()
            )
        ));
    
    }

}