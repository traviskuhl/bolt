<?php

namespace bolt\browser\response;
use \b as b;

b::response()->plug('html', '\bolt\browser\response\html');

// json
class html extends handler {

    // accept or header
    public static $contentType = array(
        100 => 'text/html'
    );

    //
    public function handle() {

        // set some content type
        $this->setContentType('text/html');

        // html
        return $this->getContent();

    }

}