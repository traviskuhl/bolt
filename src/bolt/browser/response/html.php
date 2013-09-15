<?php

namespace bolt\browser\response;
use \b as b;

// json
class html extends base {

    const TYPE = 'html';

    public $contentType = 'text/html';

    //
    public function render() {

        $html = $this->getContent();


        // html
        return $html;

    }

}