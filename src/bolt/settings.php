<?php

// bolt namespace
namespace bolt;
use \b;

// plugin to b
b::plug('settings', "\bolt\settings");

// our settings is singleton
class settings extends plugin\factory {

    // data
    private $_bucket;

    // cons
    public function __construct() {
        $this->_bucket = b::bucket(array());
    }

    // __get
    public function __get($name) {
        return $this->_bucket->get($name);
    }

    // __set
    public function __set($name, $value) {
        return $this->_bucket->set($name, $value);
    }

    public function __call($name, $args) {
        return call_user_func_array(array($this->_bucket, $name), $args);
    }


}