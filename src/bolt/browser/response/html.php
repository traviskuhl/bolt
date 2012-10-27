<?php

namespace bolt\browser\response;
use \b as b;

b::response()->plug('html', '\bolt\browser\response\html');

// json
class html extends \bolt\plugin\singleton {

    // accept or header
    public static $accept = array(
        100 => 'text/html'
    );
    
    // content type
    public $contentType = "text/html";
    
    //
    public function getContent($view) {
        
        // give it up
        $html = $view->getContent();

        echo $html; die;
        
        // is html really an array
        if (is_array($html)) {
            if (isset($html['redirect'])) {
                exit(call_user_func(array('b', 'location'), $html['redirect']));
            }
        }            
            
        // html
        return $html;
            
    }

}