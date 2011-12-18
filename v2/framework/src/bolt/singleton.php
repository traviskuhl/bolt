<?php

namespace bolt;

class singleton {

    // constant
    public static $TYPE = "singleton";

    // call
    public function __call($name, $args=array()) {
        return $this;
    }
    

}