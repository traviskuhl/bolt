<?php

// namespace
namespace bolt;

// plugin
\b::plug('dao', '\bolt\dao');


////////////////////////////////////////////////////////////
/// @brief dao implentation
////////////////////////////////////////////////////////////
class dao extends plugin\factory {

    ////////////////////////////////////////////////////////////
    /// @brief factory
    ////////////////////////////////////////////////////////////    
    public static function factory() {
        
        // args
        $args = func_get_args();
        
        // the first part of args should be 
        // the class name
        $class = array_shift($args);            
        
        // try to load this class
        if (class_exists($class, true)) {
        
            // we've got the class
            // let's create an object
            return new $class($args);
        
        }
        
        // return a default object
        return new dao\item();
    
    }

}
