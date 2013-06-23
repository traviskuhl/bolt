<?php

// bolt namespace
namespace bolt;
use \b;

// plugin to b
b::plug('settings', "\bolt\settings");

interface iSettings {
    public function get();
}

// our settings is singleton
class settings extends plugin\singleton {

     // data
     private $_bucket;

     public function __construct() {
         $this->_bucket = b::bucket(array());
     }

     // default
     public function _default($data=false) {
         if (is_array($data)) {
             $this->_bucket->set($data);
         }
         else if (is_string($data)) {
             return $this->_bucket->get($data);
         }
         return $this;
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

     public function asArray() {
         return $this->_bucket->asArray();
     }

     public function set($name, $value) {
        if (b::isInterfaceOf($value, '\bolt\iSettings')) {
            $value = $value->get();
        }
        return call_user_func(array($this->_bucket, 'set'), $name, $value);
     }


}
