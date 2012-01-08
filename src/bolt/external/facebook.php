<?php

namespace bolt\external;
use \b as b;

// register
b::external()->plug('facebook', '\bolt\external\facebook');

// include our facebook
include(bRoot."/bolt/external/facebook/facebook.php");

// facebook
class facebook extends \bolt\plugin\singleton {

    // facebook
    private $_fb = false;

    // construct
    public function __construct() {
        $this->_fb = new \Facebook(array(
            'appId'  => b::config()->fbAppId,
            'secret' => b::config()->fbAppSecret
        ));
    }
    
    // call
    public function __call($name, $args) {
        return call_user_func_array(array($this->_fb, $name), $args);
    }
    
}