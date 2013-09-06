<?php

namespace bolt\browser\response;
use \b as b;

// json
class plain extends base {

    const TYPE = 'plain';

    // accept or header
    public static $contentType = array(
        100 => 'text/plain'
    );

    //
    public function getContent() {

        // html
        return $this->getContent();

    }

}