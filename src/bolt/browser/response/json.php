<?php

namespace bolt\browser\response;
use \b as b;

b::depend("bolt-browser-response")
    ->response
    ->plug('json', '\bolt\browser\response\json');

// json
class json extends handler {

    // accept or header
    public static $contentType = array(
        100 => 'application/json',
        200 => 'application/json;secure'
    );

    //
    public function handle() {


        $resp = json_encode(array(
            'status' => $this->getStatus(),
            'response' => $this->getContent()
        ));

        // securet
        if (stripos($this->getContentType(), ';secure') !== false) {
            $resp = 'while(1);'.$resp;
        }

        // set response
        $this->setContentType("application/json");

        // give it up
        return $resp;

    }

}