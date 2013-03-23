<?php

namespace bolt\browser\response;
use \b as b;

b::response()->plug('json', '\bolt\browser\response\json');

// json
class json extends \bolt\plugin\singleton {

    // accept or header
    public static $accept = array(
        100 => 'text/javascript',
        200 => 'text/javascript;secure'
    );

    // content type
    public $contentType = "text/javascript";

    //
    public function getContent($view) {

        $resp = json_encode(array(
            'status' => b::response()->getStatus(),
            'response' => $view->getContent()
        ));

        // pretty
        $resp = (p('_pretty') ? b::jsonPretty($resp) : $resp);

        // securet
        if ($view->getAccept() == 'text/javascript;secure') {
            $resp = 'while(1);'.$resp;
        }

        // give it up
        return $resp;

    }

}