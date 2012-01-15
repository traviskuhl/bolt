<?php

// name it
namespace bolt;
use \b as b;

// plugin our session
b::plug(array(
    'session' => '\bolt\session',
    'login' => 'session::login',
    'logout' => 'session::logout',
));


class session extends plugin\singleton {

    // data
    private $_dao = false;
    private $_cname = "s";
    
    // sid
    public $_id = false;
    public $_changed = false;
        
    // when we first create our singleton
    // we need to load it
    public function start($id=false) {
    
        if (b::config()->get('session') != 'false') {        
        
            // cookie name
            $this->_cname = b::config()->get('session.cookie', 's');                    
                    
            // no sid check the cookie
            if (!$id) {
                $c = b::cookie()->get($this->_cname);
                if ($c) {
                    $id = $c['id'];                    
                }
            }

            $this->_id = $id;                        
                        
            // load
            $this->load();
            
        }
        
        // this
        return $this;
        
    }
    
    public function regenerate() {
        $this->delete();    
        $this->_id = false;
        $this->_dao = false;   
        $this->load();         
    }

    public function __destruct() {
        $this->save();
    }
    
    public function sid() {
        // no id we should save it 
        return ($this->_dao ? $this->_dao->id : false);
    }
    
    // get a value
    public function __get($key) {   
        $this->_changed = true;
        return $this->_dao->__get("data_{$key}");
    }

    // set a value
    public function __set($key, $value) {
        $this->_changed = true;    
        $this->_dao->__set("data_{$key}", $value);
    }
    
    public function set($data) {
        foreach ($data as $k => $v) {
            $this->__set($k, $v);
        }
    }
    
    // set
    public function __call($name, $args) {
        return call_user_func_array(array($this->_dao, $name), $args);
    }
    
    // load
    public function load() {
    
        // dao
        $dao = b::config()->get('session.dao', '\bolt\common\dao\sessions');    
        
        // load it
        $this->_dao = b::dao($dao);
        
            // if we have an id, load it
            if ($this->_id) {
                $this->_dao->get('id', $this->_id);
            }
        
        // if we have an a
        if ($this->_dao->account) {
            b::account()->get('id', $this->_dao->account);
        }
        
    }
    
    // write
    public function save() {
    
        if (!$this->_changed) {
            return;
        }
        
        // account
        $this->_dao->account = b::account()->id;
        
        // save
        $this->_dao->save();

        // cookie me    
        if ($this->sid()) {
            b::cookie()->set($this->_cname, array('id'=>$this->sid(),'ip'=>IP,'t'=>b::utctime()));        
        }
        
    }
    
    public function delete() {
        ($this->_dao ? $this->_dao->delete() : false);
        b::cookie()->set($this->_cname, false, b::utctime());        
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

