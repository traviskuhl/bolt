<?php

namespace bolt\plugin;

abstract class factory {

    // constant
    public static $TYPE = "factory";

    // factory
    public static function factory($args) {
    
        // paretn
        $parent = get_called_class();
        
        // rebuild our class
        return new $parent($args);
    
    }


}