<?php

namespace bolt\bucket;
use \b;

class bObject implements \bolt\iBucket {

    private $_obj = false;


    public function __construct($obj) {
        $this->_obj = $obj;
    }

    public function __get($name) {
        return $this->_obj->$name;
    }
    public function __set($name, $value) {
        return $this->_obj->$name = $value;
    }

    public function __call($name, $args) {
        if (method_exists($this->_obj, $name)) {
            return call_user_func_array(array($this->_obj, $name), $args);
        }
        return false;
    }

    public function value() {
        return $this->_obj;
    }

    public function normalize() {
        return $this->_obj;
    }

}