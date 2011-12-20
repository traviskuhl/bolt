<?php

// name it
namespace bolt;
use \b as b;

// plugin our session
b::plug(array(
    'session' => '\bolt\session',
    'login' => function() { return call_user_func(array(b::session(), 'login'), func_get_args()); },
    'logout' => function() { return call_user_func(array(b::session(), 'logout'), func_get_args()); },
));


class session extends plugin\singleton {

    // data
    private $_data = array();
    private $_sid = false;
    private $_store = false;
    
    
    // when we first create our singleton
    // we need to load it
    public function __construct() {
    
        // if we shouldn't load the session
        if (b::config()->get('session') != 'false') {    
            $this->load();
        }
        
    }

    public function __destruct() {
        $this->save();
    }
    
    // get a value
    public function __get($key) {
        return $this->_data[$key];
    }

    // set a value
    public function __set($key, $value) {
        return ($this->_data[$key] = $value);
    }
    
    // load
    public function load() {
    
        // figure out what dao to use
        $dao = b::config()->get('session.dao', '\bolt\dao\session');
    
    }
    
    // write
    public function save() {
    
    }

    // verify
    public function verify() {
    
    }

    // 
    public function login() {
    
    }
    
    // logout
    public function logout() {
    
    }


}

