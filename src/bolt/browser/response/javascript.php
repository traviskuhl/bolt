<?php

namespace bolt\browser\response;
use \b as b;


b::depend("bolt-browser-response")
    ->response
    ->plug('javascript', '\bolt\browser\response\javascript');

// json
class javascript extends handler {

    // accept or header
    public static $contentType = array(
        100 => 'text/javascript'
    );

    //
    public function handle() {

        $this->setContentType('text/javascript');

        // javascript
        return $this->getContent();

    }

}