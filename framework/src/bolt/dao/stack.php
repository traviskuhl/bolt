<?php

namespace bolt\dao;

class stack extends \SplStack {

    private $map = array();
    
    // loaded
    public function loaded() { return $this->count(); }

    public function push($item, $key=false) {
            
        // push to stak
        parent::push($item);
        
        // i
        $i = $this->key();
        
        // no key
        if ($key === false) { $key = $i; }
    
        // if key add it to the map
        $this->map[$key] = $i;
    
    }

    // item
    public function item($idx=0) {

        // what up
        switch($idx) {
        
            // first item
            case 'first':
                $idx = array_shift(array_slice($this->map,0,1)); break;
                
            // last item
            case 'last':
                $idx = array_shift(array_slice($this->map,-1)); break;
                
            // else
            default:
                if (array_key_exists($idx, $map)) {
                    $idx = $map[$idx];
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

}

