<?php

namespace bolt\browser\response;
use \b as b;

b::response()->plug('json', '\bolt\browser\response\json');

// json
class json extends \bolt\plugin\singleton {

    // accept or header
    public static $accept = array(
        100 => 'text/javascript'
    );
    
    // content type
    public $contentType = "text/javascript";
    
    //
    public function getContent($view) {
        
        // give it up
        return json_encode(array(
            'status' => $view->getStatus(),
            'response' => $view->getData()
        ));
    
    }

}