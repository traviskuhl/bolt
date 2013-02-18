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

        // layout
        $layout = '{child}';

        // see if there's a layout
        if ($controller->hasLayout()) {
            $layout = $controller
                        ->getLayout()
                            ->render()
                            ->getContent();
        }

        $html = str_replace('<% child %>', $controller->getContent(), $layout);

        // html
        return $html;

    }

}