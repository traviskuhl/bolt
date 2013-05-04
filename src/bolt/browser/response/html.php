<?php

namespace bolt\browser\response;
use \b as b;

b::response()->plug('html', '\bolt\browser\response\html');

// json
class html extends \bolt\plugin\singleton {

    // accept or header
    public static $contentType = array(
        100 => 'text/html'
    );

    //
    public function getContent($controller) {

        b::response()->setContentType('text/html');

        // html
        return $controller->getContent();

    }

}