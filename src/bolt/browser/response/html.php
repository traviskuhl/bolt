<?php

namespace bolt\browser\response;
use \b as b;

b::response()->plug('html', '\bolt\browser\response\html');

// json
class html extends \bolt\plugin\singleton {

    // accept or header
    public static $contentType = array(
        100 => 'text/html'
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