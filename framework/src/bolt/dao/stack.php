<?php

namespace bolt\dao;

class stack extends \SplStack {

    private $_map = array();
    private $_item = false; 
    
    public function __construct() {
        $this->_item = new item($this, array());
    }
    
    // loaded
    public function loaded() { 
        if (!$this->count()) {
            return $this->_item->loaded();
        }
        else {
            return $this->count(); 
        }
    }
    
    // get 
    public function __get($name) {       
        return $this->getItem()->$name;
    }
    
    // set
    public function __set($name, $value) {
        return $this->getItem()->$name = $value;
    }
    
    // call
    public function __call($name, $args) {    
        return call_user_func_array(array($this->getItem(), $name), $args);
    }

    public function getItem() {
        return $this->_item;
    }

    public function setItem($item) {
        if (is_array($item)) {
           return $this->_item->set($item);
        }
        else if (is_object($item)) {
            return $this->_item->set($item->getData());
        }    
        return $this->_item;
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

