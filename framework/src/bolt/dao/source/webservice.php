<?php

namespace bolt\dao\source;
use \b as b;

abstract class webservice extends \bolt\dao\stack {

    // collection
    protected $wsConfig = false;
    private $_ws = false;

    // construct
    public function __construct() {    
        $this->_ws = b::webservice($this->wsConfig);
    }
    
    // request
    public function request() {
        return call_user_func_array(array($this->_ws, 'request'), func_get_args());
    }
    
    // get and set pass
    public function __get($name) {
        return $this->_ws->__get($name);
    }
    
    // set 
    public function __set($name, $value) {
        return $this->_ws->__set($name, $value);
    }

}
