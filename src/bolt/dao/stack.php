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
        parent::unshift($item);
        
        // i
        $i = $this->count() - 1;
        
        // no key
        if ($key === false) { $key = $i; }
    
        // if key add it to the map
        $this->_map[$key] = $i;
    
        // chainable
        return $this;
    
    }

    // item
    public function item($idx=0) {

        // what up
        switch($idx) {
        
            // first item
            case 'first':            
                $idx = $this->count()-1;                
                break;
                
            // last item
            case 'last':
                $idx = 1; break;
                
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
    
    public function import($items) {
        foreach ($items as $key => $item) {
            $this->push($item, $key);
        }
        return $this;
    }
    
    public function reduce($cb) {      
        $resp = array();
        
        // cb is a string
        if(is_string($cb)) {
            if ($cb{0} == '$') {
                $key = substr($cb,1);
                $cb = function($item) use ($key) {     
                    $i = $item->$key;
                        if (is_object($i)) {
                            $i = $i->asArray();
                        }
                    return $i;
                };            
            }
        }        
    
        foreach ($this as $item) {
            if (($r = $cb($item)) !== false) { 
                if (is_array($r)) {
                    $resp = array_merge($r, $resp);
                }
                else {
                    $resp[] = $r;
                }
            }
        }
    
        // gives back a new stack
        $s = new stack();
        
            // loop        
            foreach (array_unique($resp) as $i) {
                $s->push($i);
            }
        
        // give bacl
        return $s;
    
    } 
    
    public function map($cb) {      
        $resp = array();
        
        // cb is a string
        if(is_string($cb)) {
            if ($cb{0} == '$') {
                $key = substr($cb,1);
                $cb = function($item) use ($key) {     
                    return $item->$key;
                };            
            }
        }        
    
        foreach ($this as $item) {
            if (($r = $cb($item)) !== false AND !is_array($r)) {
               $resp[] = $r;
            }
        }
    
        // gives back a new stack
        $s = new stack();
        
            // loop        
            foreach ($resp as $i) {
                $s->push($i);
            }
        
        // give bacl
        return $s;
    
    }     
    
    public function filter($cb) {      
        $s = new stack();
    
        foreach ($this as $item) {
            if ($cb($item) !== false) {
                $s->push($item);
            }
        }
    
        // give bacl
        return $s;
    
    }     

    public function sort($cb, $sort=false) {
        $s = new stack();
        
        // array
        $array = array();
    
        foreach ($this as $key => $item) {
            $array[$key] = (is_callable($cb) ? $cb($item) : $item->$cb);
        }
        
        // sort it 
        if (is_callable($sort)) {
            uasort($array, $sort);
        }
        else if ($sort) {
            arsort($array);
        }
        else {
            asort($array);
        }
        
        foreach ($array as $key => $item) {
            $s->push($this->item($key));
        }
    
        // give bacl
        return $s;
    
    }   
    
    public function each($cb) {      
        $resp = array();
    
        foreach ($this as $key => $item) {
            $resp[$key] = $cb($item);
        }
    
        // give bacl
        return $resp;
    
    }             
    
    public function asArray() {
        $array = array();
        foreach ($this as $item) {
            if (is_object($item) AND method_exists($item, 'asArray')) {
                $array[] = $item->asArray();            
            }
            else {
                $array[] = $item;
            }
        }
        return $array;
    }

}

