<?php

namespace bolt\dao;

class stack extends \SplStack {

    private $_map = array();
    private $_item = false; 
    
    // loaded
    public function loaded() { 
        if ($this->_item) {
            return $this->_item->loaded();
        }
        else {
            return $this->count(); 
        }
    }
    
    // get 
    public function __get($name) {
        $this->initItem();        
        return $this->_item->__get($name);
    }
    
    // set
    public function __set($name, $value) {
        $this->initItem();
        return $this->_item->__set($name, $value);
    }
    
    // call
    public function __call($name, $args) {
        $this->initItem();
        if (method_exists($this->_item, $name)) {
            return call_user_func_array(array($this->_item, $name), $args);
        }
        return false;
    }

    public function getItem() {
        $this->initItem();    
        return $this->_item;
    }
    
    public function setItem($item=false) {    
        $this->_item = $item;
    }
    
    public function initItem() {
        if ($this->_item) {return;}
        $this->_item = new \bolt\dao\item($this, array());
    }
    
    public function adjunct($name, $val) {
        $this->initItem();
        $this->_item->_adjunct[$name] = $val;
    }

    // struct
    public function getStruct() { 
        return array();
    }

    // push
    public function push($item, $key=false) {            
            
        // if item is an array
        // lets turn it into an obj
        if (is_array($item)) {
            $item = new \bolt\dao\item($this, $item);
        }
            
        // push to stak
        parent::push($item);
        
        // i
        $i = $this->count() - 1;
        
        // no key
        if ($key === false) { $key = $i; }
    
        // if key add it to the map
        $this->_map[$key] = $i;
    
    }

    // item
    public function item($idx=0) {

        // what up
        switch($idx) {
        
            // first item
            case 'first':
                $idx = array_shift(array_slice($this->_map,0,1)); break;
                
            // last item
            case 'last':
                $idx = array_shift(array_slice($this->_map,-1)); break;
                
            // else
            default:
                if (array_key_exists($idx, $this->_map)) { 
                    $idx = $this->_map[$idx];
                }
        };
       
        // nope
        if ($this->offsetExists($idx)) {
            return $this->offsetGet($idx);
        }
        else {
            return false;
        }
    
    }
    
    public function asArray() {
        $array = array();
        foreach ($this as $item) {
            $array[] = $item->asArray();
        }
        return $array;
    }

}

