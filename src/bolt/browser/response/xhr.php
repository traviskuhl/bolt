<?php

namespace bolt\browser\response;
use \b as b;

b::response()->plug('xhr', '\bolt\browser\response\xhr');

// json
class xhr extends handler {

    // accept or header
    public static $contentType = array(
        100 => 'text/javascript;text/xhr'
    );


    //
    public function handle() {

        $this->setContentType("text/javascript");

        // body
        $body = $this->getContent();

        // holders
        $js = array();

        // script
        $body = preg_replace("#<script([^>]+)?(\>)#", "<script$1%%E%%", $body);

        // replace any </stript>'s'
        $body = str_replace(
            array(
                "</script>",
                ">",
                "%%E%%"
            ),
            array(
                "</script%%E%%",
                "%%EE%%",
                ">"
            ),
            $body);


        // pick our javascript and css
        // need to remove comments
        $body = preg_replace(array("/\/\/[a-zA-Z0-9\s\&\?\.]+\n/", "/\/\*(.*)\*\//"), " ", $body);

        // if yes remove
        if ( preg_match_all("/((<[\s\/]*script\b[^>]*>)([^>]*)(<\/script>))/i", $body, $_js) ) {
            $js = @$_js[3];
            if ($js) {
                $js = array_map(function($str){ return str_replace(array("%%EE%%", "%%E%%"),">", $str); }, $js);
                $body = str_replace(array("%%EE%%", "%%E%%"),">", $body);
                foreach ($_js[0] as $l) {
                    $body = str_replace($l, "", $body);
                }
            }
        }



        // give it up
        return json_encode(array(
            'status' => $this->getStatus(),
            'response' => array(
                'content' => $body,
                'data' => $this->getData(),
                'bootstrap' => array(
                    'javascript' => $js,
                )
            )
        ));

    }

}