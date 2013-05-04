<?php

namespace bolt\browser\response;
use \b as b;

b::response()->plug('ajax', '\bolt\browser\response\ajax');

// json
class ajax extends \bolt\plugin\singleton {

    // accept or header
    public static $contentType = array(
        100 => 'text/javascript;text/ajax'
    );


    //
    public function getContent($controller) {

        b::response()->setContentType('text/javascript');

        // give it up
        return json_encode(array(
            'status' => $controller->getStatus(),
            'response' => array(
                'content' => $controller->getContent(),
                'data' => $controller->getData()
            )
        ));

    }

}