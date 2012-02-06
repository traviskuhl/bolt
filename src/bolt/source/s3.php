<?php

// namespace me
namespace bolt\source;
use \b;

// plugin our global instance directly to bolt
b::plug('s3', '\bolt\source\s3');

// mongo
class s3 extends \bolt\plugin\singleton {

    private $instance = false;

    public function __construct($args=array()) {  
        $c = b::config()->s3;
        if ($c) {    
            $this->instance = b::external()->s3($c['key'], $c['secret']);
        }
    }
    
    // call it
    public function __call($name, $args) {
        return call_user_func_array(array($this->instance, $name), $args);
    }

}