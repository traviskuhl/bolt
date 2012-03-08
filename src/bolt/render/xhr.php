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
        
        // body
        $body = $h->render($view);
        
        // holders
        $js = $css = array();
                
        // pick our javascript and css
		// need to remove comments
		$body = preg_replace(array("/\/\/[a-zA-Z0-9\s\&\?\.]+\n/", "/\/\*(.*)\*\//"), " ", $body);

		// if yes remove
		if ( preg_match_all("/((<[\s\/]*script\b[^>]*>)([^>]*)(<\/script>))/i", $body, $_js) ) {
			$body = preg_replace("/((<[\s\/]*script\b[^>]*>)([^>]*)(<\/script>))/i", "", $body);
			$js = @$_js[3];
		}
        
        // give it up
        return json_encode(array(
            'status' => $view->getStatus(),
            'response' => array(
                'html' => $body,
                'data' => $view->getData(),
                'bootstrap' => array(
                    'javascript' => $js,
                    'css' => $css
                )
            )
        ));
    
    }

}