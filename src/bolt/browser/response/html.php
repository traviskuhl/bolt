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
                    'clean' => false,
                    'hide-comments' => true,
                    'indent' => true,
                    'wrap' => 0,
                    'join-styles' => false,
                    'markup' => true,
                    'doctype' => '<!DOCTYPE HTML>',
                    'new-blocklevel-tags'   => 'menu,mytag,article,header,footer,section,nav',
                    'new-inline-tags'       => 'video,audio,canvas,ruby,rt,rp,time',
                ));

            tidy_clean_repair($tidy);

            $html = (string)$tidy;

        }

        // html
        return $html;

    }

}