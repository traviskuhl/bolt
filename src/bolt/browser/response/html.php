<?php

namespace bolt\browser\response;
use \b as b;

b::response()->plug('html', '\bolt\browser\response\html');

// json
class html extends \bolt\plugin\singleton {

    // accept or header
    public static $accept = array(
        100 => 'text/html'
    );

    // content type
    public $contentType = "text/html";

    //
    public function getContent($controller) {

        // child
        $html = $controller->getContent();

        // see if there's a layout
        if ($controller->hasLayout()) {
            $html = $controller
                        ->getLayout()
                            ->render(array('child' => $html))
                            ->getContent();
        }

        // html
        return $html;

    }

}