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

        // eval is evil. you should never ever do this
        // but i'm doing it anways... because i'm evil
        // and not the diet coke kind of evil
        eval(' ?>'.$string.'<?php ');

        $content = ob_get_contents(); ob_end_clean();

        // content
        return $content;
    }

}