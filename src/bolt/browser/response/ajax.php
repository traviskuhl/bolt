<?php

namespace bolt\browser\response;
use \b as b;


// json
class ajax extends base {

    const TYPE = 'ajax';

    public $contentType = 'text/javascript';

    //
    public function render() {

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