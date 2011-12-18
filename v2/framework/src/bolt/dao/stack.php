<?php

namespace bolt\dao;

class stack extends \SplStack {

    private $map = array();

    public function push($item, $key=false) {
        
        // i
        $i = $this->count();
        
        // push to stak
        parent::push($item);
    
        // if key add it to the map
        $this->map[$key] = $i;
    
    }

    // item
    public function item($idx=0) {

        // what up
        switch($item) {
        
            // first item
            case 'first':
                $item = array_shift(array_slice($this->map,0,1)); break;
                
            // last item
            case 'last':
                $idx = array_shift(array_slice($this->map,-1)); break;
                
            // else
            default:
                if (array_key_exists($idx, $map)) {
                    $idx = $map[$idx];
                }
                else {
                    $idx = $idx;
                }
        };
    
        // give it 
        return $this->getOffset($idx);
    
    }

}

