<?php

namespace bolt;

abstract class factory {

    // constant
    public static $TYPE = "factory";

    // factory
    public static function factory($args) {
    
        // paretn
        $parent = get_parent_class();
        
        // rebuild our class
        return new $parent($args);
    
    }


}