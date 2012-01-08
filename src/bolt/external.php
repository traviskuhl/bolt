<?php

// namespace
namespace bolt;
use \b as b;

// plugin
\b::plug('external', '\bolt\external');


////////////////////////////////////////////////////////////
/// @brief dao implentation
////////////////////////////////////////////////////////////
class external extends plugin {
 
    // type is singleton 
    // since this is really a plugin dispatch
    public static $TYPE = "singleton";
    
    // init
    public function init($name, $plugToBolt=false) {
        
        // include
        include(bRoot."/bolt/external/{$name}.php");
    
        // if yes
        if ($plugToBolt) {
            b::plug("$name", function($sub=false, $args=array()) use ($name) {
                if ($sub) {
                    return call_user_func_array(array(b::external()->call($name), $sub), $args);
                }            
                return b::external()->call($name);
            });
        }
    
    }

}
