<?php

namespace bolt\browser\response;
use \b as b;


// json
class json extends base {

    const TYPE = 'json';

    public $contentType = 'application/json';

    //
    public function render() {

        $resp = json_encode(array(
            'status' => $this->getStatus(),
            'response' => $this->getContent()
        ));

        // give it up
        return $resp;

    }

}