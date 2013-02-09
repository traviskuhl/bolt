<?php

// namespace me
namespace bolt\cli;
use \b as b;


// plugin to instance source factory
b::cli()->plug('arguments', '\bolt\cli\arguments');

// singleton class
class arguments extends \bolt\plugin\singleton {

    public function __construct() {
        $this->instance = new \cli\Arguments(array('strict' => false));
    }

    public function __call($name, $args) {
        if (method_exists($this->instance, $name)) {
            return call_user_func_array(array($this->instance, $name), $args);
        }
        return false;
    }

}