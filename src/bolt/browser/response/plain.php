<?php

namespace bolt\browser\response;
use \b as b;

b::response()->plug('plain', '\bolt\browser\response\plain');

// json
class plain extends \bolt\plugin\singleton {

    // accept or header
    public static $contentType = array(
        100 => 'text/plain'
    );

    //
    public function getContent($controller) {

        // child
        $html = $controller->getContent();

        // see if there's a layout
        if ($controller->hasLayout()) {

            $html = $controller
                        ->getLayout()
                            ->setParams(array('child' => $html))
                            ->render();
        }

        // html
        return $html;

    }

}