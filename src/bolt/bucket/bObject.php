<?php

namespace bolt\bucket;
use \b;

class bObject implements \bolt\iBucket {

    private $_obj = false;


    public function __construct($obj) {
        $this->_obj = $obj;
    }

    public function __get($name) {
        if ($name == 'value') { return $this->_obj; }
        return $this->_obj->$name;
    }
    public function __set($name, $value) {
        return $this->_obj->$name = $value;
    }

    public function __toString() {
        return (string)call_user_func(array($this, '__call'), '__toString', func_get_args());
    }

    public function __isset($name) {
        return call_user_func(array($this, '__call'), '__isset', func_get_args());
    }

    public function __unset($name) {
        return call_user_func(array($this, '__call'), '__unset', func_get_args());
    }

    public static function __callStatic($name, $args) {
        return call_user_func(array($this, '__call'), '__callStatic', func_get_args());
    }

    public function __sleep() {
        return call_user_func(array($this, '__call'), '__sleep', func_get_args());
    }

    public function __wakeup() {
        return call_user_func(array($this, '__call'), '__wakeup', func_get_args());
    }

    public function __invoke() {
        return call_user_func(array($this, '__call'), '__invoke', func_get_args());
    }

    public function __call($name, $args) {
        if (method_exists($this->_obj, $name)) {
            return call_user_func_array(array($this->_obj, $name), $args);
        }
        else if (method_exists($this->_obj, '__call')) {
            return call_user_func(array($this->_obj, '__call'), $name, $args);
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