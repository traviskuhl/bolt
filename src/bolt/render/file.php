<?php

namespace bolt\render;
use \b;

class file extends base {

    public static $extension = array('html','txt');


    public function compile($str) {

        return $str;
    }

    public function render($str, $vars=array()) {

        // give it back
        return $str;

    }

}