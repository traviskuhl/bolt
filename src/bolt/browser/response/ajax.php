<?php

namespace bolt\browser\response;
use \b as b;

b::response()->plug('ajax', '\bolt\browser\response\ajax');

// json
class ajax extends handler {

    // accept or header
    public static $contentType = array(
        100 => 'text/javascript;text/ajax'
    );

    //
    public function handle() {

        // reset content type
        $this->setContentType('text/javascript');

        // give it up
        return json_encode(array(
            'status' => $this->getStatus(),
            'response' => array(
                'content' => $this->getContent(),
                'data' => $this->getData()
            )
        ));

    }

}