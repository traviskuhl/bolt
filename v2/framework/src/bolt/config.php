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
    public function __default($args=array()) {        
        // forward it to call
        return $this->merge($args);
    }

    // override our call method
    public function __call($name, $args) {
        if ($name == 'set') {
            return $this->set(array_shift($args), array_shift($args));
        }
        else if ($name == 'get') {
            return $this->get(array_shift($args));
        }
        else if ($name == 'merge') {
            return call_user_func_array(array($this, 'merge'), $args);
        }
        else {    
            return $this;
        }
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