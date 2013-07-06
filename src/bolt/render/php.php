<?php

namespace bolt\render;
use \b;

class php extends base {

    public static $extension = array('php');

    public function render($string, $vars) {

        // globalize our variables
        foreach ($vars as $k => $v) {
            $$k = (is_object($v) ? $v : b::bucket($v));
        }

        ob_start();

            eval(' ?>'.$string.'<?php ');

        $content = ob_get_contents(); ob_end_clean();

        // content
        return $content;
    }

}