<?php

namespace bolt\browser\response;
use \b as b;


b::depend("bolt-browser-response")
    ->response
    ->plug('plain', '\bolt\browser\response\plain');

// json
class plain extends handler {

    // accept or header
    public static $contentType = array(
        100 => 'text/plain'
    );

    //
    public function handle() {

        // html
        return $this->getContent();

    }

}