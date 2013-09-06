<?php

namespace bolt\browser\response;
use \b as b;


// json
class javascript extends base {

    const TYPE = 'js';

    // accept or header
    public $contentType = 'text/javascript';

    //
    public function handle() {

        // javascript
        return $this->getContent();

    }

}