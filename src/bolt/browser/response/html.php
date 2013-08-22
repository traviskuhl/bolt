<?php

namespace bolt\browser\response;
use \b as b;

b::depend("bolt-browser-response")
    ->response
    ->plug('html', '\bolt\browser\response\html');

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

        $html = $this->getContent();

        if (function_exists('tidy_parse_string') AND b::env() != 'dev') {

            $tidy = tidy_parse_string($html, array(
                    'clean' => true,
                    'hide-comments' => true,
                    'indent' => false,
                    'wrap' => 0
                ));

            tidy_clean_repair($tidy);

            $html = (string)$tidy;

        }

        // html
        return $html;

    }

}