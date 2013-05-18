<?php

namespace bolt\browser\response;
use \b as b;

b::response()->plug('javascript', '\bolt\browser\response\javascript');

// json
class javascript extends \bolt\plugin\singleton {

    // accept or header
    public static $contentType = array(
        100 => 'text/javascript'
    );

    //
    public function getContent($controller) {

        b::response()->setContentType('text/javascript');

        // javascript
        return $controller->getContent();

    }

}