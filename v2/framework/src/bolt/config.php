<?php

// bolt namespace
namespace bolt;

// plugin to b
\b::plug('config', "\bolt\config");

// our config is singleton
class config extends singleton {

    // data
    private $data = array();

    // default 
    public function _default() {        
        // forward it to call
        return call_user_func_array(array($this, 'merge'), func_get_args());
    }
    
    // __get 
    public function __get($name) {
        return $this->get($name);
    }
    
    // __set
    public function __set($name, $value) {
        return $this->set($name, $value);
    }
    

    // get
    public function get($name) {
        return (array_key_exists($name, $this->data) ? $this->data[$name] : false);
    }

    // set
    public function set($name, $value=false) {
        return $this->data[$name] = $value;
    }
    
    // merge
    public function merge() {    
        foreach (func_get_args() as $array) { 
            foreach ($array as $key => $value) {
                $this->set($key, $value);
            }
        }        
        return $this;
    }

}