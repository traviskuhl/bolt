<?php

// namespace me
namespace bolt\cli;
use \b as b;


// plugin to instance source factory
b::cli()->plug('arguments', '\bolt\cache\arguments');

// singleton class
class arguments extends \bolt\plugin\singleton {

    public function __construct() {
        $this->instance = new \cli\Arguments();
    }

    public function __call($name, $args) {
        if (method_exists($this, $name)) {
            return call_user_func_array(array($this, $name), $args);
        }
        return false;
    }

}