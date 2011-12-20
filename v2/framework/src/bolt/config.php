<?php

// bolt namespace
namespace bolt;

// plugin to b
\b::plug('config', "\bolt\config");

// our config is singleton
class config extends plugin\singleton {

    // data
    private $_data = array();

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
    public function get($name, $default=false) {
    
        // see if we have a . in the name
        if (stripos($name, '.') !== false) {
            
            // get the parts
            $parts = explode('.', $name);
            $a = $this->_data;
        
            // loop through and see if we have this 
            foreach ($parts as $p) {
                if (array_key_exists($p, $a)) {
                    $a = $a[$p];
                }
                else {
                    return $default;
                }
            }
        
            // return it
            return $a;
        
        }
        else {    
            return (array_key_exists($name, $this->_data) ? $this->_data[$name] : $default);
        }
    }

    // set
    public function set($name, $value=false) {
        return ($this->_data[$name] = $value);
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