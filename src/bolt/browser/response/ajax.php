<?php

namespace bolt\browser\response;
use \b as b;

b::response()->plug('ajax', '\bolt\browser\response\ajax');

// json
class ajax extends \bolt\plugin\singleton {

    // accept or header
    public static $accept = array(
        100 => 'text/javascript;text/ajax'
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