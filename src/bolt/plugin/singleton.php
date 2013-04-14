<?php

namespace bolt\plugin;

class singleton extends \bolt\event {

    // constant
    public static $TYPE = "singleton";

    // call
    public function __call($name, $args) {
    	if (method_exists($this, $name)) {
    		return call_user_func_array(array($this, $name), $args);
    	}    	
        return $this;
    }


}