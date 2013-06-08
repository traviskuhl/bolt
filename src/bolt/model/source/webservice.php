<?php

namespace bolt\dao\source;
use \b as b;

abstract class webservice extends \bolt\dao\item {

    // collection
    protected $wsConfig = false;
    private $_ws = false;

    // construct
    public function __construct() {    
    
        // parent
        parent::__construct();
    
        // setup ws
        $this->_ws = b::webservice($this->wsConfig);
        
    }
    
    // request
    public function request() {
        return call_user_func_array(array($this->_ws, 'request'), func_get_args());
    }
    
    // get and set pass
    public function ws() {
        return $this->_ws;
    }

}
