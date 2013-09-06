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

        if (function_exists('tidy_parse_string')) {

            $tidy = tidy_parse_string($html, array(
                    'clean' => true,
                    'hide-comments' => true,
                    'indent' => true,
                    'wrap' => 0
                ));

            tidy_clean_repair($tidy);

            $html = (string)$tidy;

        }

        // html
        return $html;

    }

}