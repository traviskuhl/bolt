<?php

namespace bolt\plugin;

class singleton extends \bolt\event {

    // constant
    public static $TYPE = "singleton";

    // call
    public function __call($name, $args) {
        return $this;
    }


}