<?php

namespace bolt\dao;
use \b as b;

class iterator extends \ArrayIterator {
    
    // item
    public function item($idx=0) {

        // what up
        switch($idx) {
        
            // first item
            case 'first':
                $this->seek(0);
                $idx = $this->key();
                break;
                
            // last item
            case 'last':
                $this->seek($this->count()-1);
                $idx = $this->key();
                break;

        };
        
        $this->rewind();
       
        // nope
        if ($this->offsetExists($idx)) {
            return $this->offsetGet($idx);
        }
        else {
            return false;
        }
    
    }
    
    // set
    public function __set($name, $val) { 
        $this->offsetSet($name, $val);
    }

}