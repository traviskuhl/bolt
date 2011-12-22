<?php

namespace bolt;

abstract class plugin {

    // plugins
    private $_plugin = array();
    private $_instance = array();
    private $_fallback = array();
    
    // let's construct
    public function __construct($fallback=array()) {
        $this->setFallbacks($fallback);        
    }
    
    // plugin
    public function getPlugins() {
        return $this->_plugin;
    }
    
    // fallback
    public function setFallbacks($classes) {
        if (is_string($classes)) {
            $classes = array($classes);
        }
        return $this->_fallback = array_merge($this->_fallback, $classes);
    }   
    
    // get fallcacks
    public function getFallbacks() {
        return $this->_fallback;
    }
        
    // call something
    public function __call($name, $args) {
        return $this->call($name, $args);
    }

    ////////////////////////////////////////////////////////////
    /// @brief call one of our plugins
    ////////////////////////////////////////////////////////////
    public function call($name, $args=array()) {
    
        // func
        $method = false;    
    
        // see if this needs to be routed 
        // to another 
        if (stripos($name, '.') !== false) {
        
            // split into parts
            $parts = explode('.', $name);
            
            // first part
            $name = array_shift($parts);
            
            // set the rest of parts as $args
            if (array_key_exists($name, $this->_plugin)) {
                return call_user_func(array($this->_plugin[$name], 'call'), array_merge(array(implode('.', $parts)), $args));
            }
            else {
                return false;
            }
            
        }
        
        // do we not have a plugin for this
        if (!array_key_exists($name, $this->_plugin)) {
            
            // loop through our fallbacks
            foreach ($this->_fallback as $class) {
                if (method_exists($class, $name)) {
                    return call_user_func_array(array($class, $name), $args);
                }                
            }
        
            // we go nothing
            return false;

        }
        
        // get it 
        $plug = $this->_plugin[$name];        
        
        // figure out if there's a function to direct to
        if (strpos($plug, '::')!== false) {
        
            // get the orig plugin name
            list($name, $method) = explode('::', $plug);                      
            
            // reset plug
            $plug = $this->_plugin[$name];
            
        }            
        
        // is plug callable
        if (is_callable($plug)) {
            return call_user_func_array($plug, $args);
        }    
        
        // ask the class what it is
        if ($plug::$TYPE == 'factory') {
            return call_user_func_array(array($plug, "factory"), $args);
        }
        
        // singleton 
        else if ($plug::$TYPE == 'singleton') {        
                                    
            // if we don't have an instance
            if (!array_key_exists($name, $this->_instance)) {  
                $this->_instance[$name] = new $plug();
            }        
            
            // instance
            $i = $this->_instance[$name];     
                       
            // is it a string
            if ($method) {
                return call_user_func_array(array($i, $method), $args);
            }
            else if (isset($args[0]) AND is_string($args[0]) AND method_exists($i, $args[0]) ){ 
                return call_user_func_array(array($i, array_shift($args)), $args);
            }            
            else if (method_exists($i, "_default")) {            
                return call_user_func_array(array($i, "_default"), $args);
            }
            else {
                return $i;
            }
            
                        
        }
        
    }

    ////////////////////////////////////////////////////////////
    /// @brief plugin to bolt
    ////////////////////////////////////////////////////////////
    public function plug($name, $class=false) {
        
        // is it an array
        if (is_array($name)) {
            foreach ($name as $n => $c) {
                $this->_plugin[$n] = $c;
            }
        }
        
        // just one
        else {    
            $this->_plugin[$name] = $class;
        }
        
        // good
        return true;
        
    }


}