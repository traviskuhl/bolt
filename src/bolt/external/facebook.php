<?php

namespace bolt\external;
use \b as b;

// register
b::external()->plug('facebook', '\bolt\external\facebook');

// include our facebook
include(bRoot."/bolt/external/facebook/base_facebook.php");

// our localfacebook
class bFacebook extends \BaseFacebook {
  protected function setPersistentData($key, $value) {}
  protected function getPersistentData($key, $default = false) {}
  protected function clearPersistentData($key) {}  
  protected function clearAllPersistentData() {}
}

// facebook
class facebook extends \bolt\plugin\singleton {

    // facebook
    private $_fb = false;

    // construct
    public function __construct() {
        $this->_fb = new bFacebook(array(
            'appId'  => b::config()->fb['id'],
            'secret' => b::config()->fb['secret']
        ));
    }
    
    // call
    public function __call($name, $args) {
        return call_user_func_array(array($this->_fb, $name), $args);
    }
    
}