<?php

// namespace me
namespace bolt\cli;
use \b as b;


// plugin to instance source factory
b::cli()->plug('table', '\bolt\cache\table');

// singleton class
class table extends \bolt\plugin\factory {

    public function __construct() {
        $this->instance = new \cli\Table();
    }

    public function __call($name, $args) {
        if (method_exists($this, $name)) {
            return call_user_func_array(array($this, $name), $args);
        }
        return false;
    }

}