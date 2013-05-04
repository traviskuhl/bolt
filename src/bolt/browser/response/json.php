<?php

namespace bolt\browser\response;
use \b as b;

b::response()->plug('json', '\bolt\browser\response\json');

// json
class json extends \bolt\plugin\singleton {

    // accept or header
    public static $contentType = array(
        100 => 'text/javascript',
        200 => 'text/javascript;secure'
    );

    //
    public function getContent($view) {

        $resp = json_encode(array(
            'status' => b::response()->getStatus(),
            'response' => $view->getContent()
        ));

        // securet
        if (stripos($view->getContentType(), ';secure') !== false) {
            $resp = 'while(1);'.$resp;
        }

        // set response
        b::response()->setContentType("text/javascript");

        // give it up
        return $resp;

    }

}