<?php

namespace bolt;
use \b as b;

// plug
b::plug('account', '\bolt\account');

// class
class account extends \bolt\plugin\singleton {

    // dao
    private $_dao = false;

    public function __construct() {    
        $this->_dao = b::dao('\bolt\common\dao\accounts');    
    }
    
    // call
    public function __call($name, $args) {    
        return call_user_func_array(array($this->_dao, $name), $args);
    }
    
    // get set
    public function __get($name) {
        return $this->_dao->$name;
    }
    
    // set
    public function __set($name, $value) {
        return $this->_dao->$name = $value;
    }
    
    public function setAccount($account) {
        if (is_object($account) AND $account->loaded()) {
            $this->_dao = $account;
        }
        return $this;
    }
    
    // create
    public function create($data) {
        
        $name = explode(' ', $data['name']);
        
        $data['firstname'] = array_shift($name);
        $data['lastname'] = implode(" ", $name);
        
        // unset
        unset($data['name']);
        
        // password
        $data['password'] = b::crypt($data['password'], b::_("salt"));
        
        // username
        $data['email'] = strtolower(p('email', "", $data));        
        $data['username'] = strtolower(p('username', "", $data));
        
        // new 
        $this->_dao->set($data);
        
        // save
        $this->_dao->save();
        
        // return
        return $this->_dao;
    
    }
    
    public function factory() {
        return b::dao('\bolt\common\dao\accounts');
    }

}