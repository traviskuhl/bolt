<?php

// name it
namespace bolt;

// use
use \b;
use \Exception;


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
    private $_cookie = array();
    
    // sid
    public $_id = false;
    public $_changed = false;
        
    // when we first create our singleton
    // we need to load it
    public function start($id=false) {
    
        // id is not sid
        if ($this->_dao AND $id AND $id != $this->_id) {
            return $this->regenerate($id);
        }
    
        // if loaded we can stop
        if ($this->_dao !== false) { return $this; }
    
        // get it
        if (b::config()->get('session') != 'false') {        
        
            // cookie name
            $this->_cname = b::config()->get('session.cookie', 's');                    
                    
            // no sid check the cookie
            if (!$id) {
                
                // get the cookie
                $this->_cookie = b::cookie()->get($this->_cname);
                
                if ($this->_cookie) {
                    $id = $this->_cookie['id'];                    
                }
            }

            $this->_id = $id;                        
                        
            // load
            $this->load();
            
        }
        
        // this
        return $this;
        
    }
    
    public function regenerate($id=false) {
        $this->delete();    
        $this->_dao = false;
        $this->_id = $id;           
        $this->load();
        return $this;         
    }

    public function __destruct() {
        $this->save();
    }
    
    public function getSid() {
        return $this->sid();
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
            b::cookie()->set($this->_cname, b::mergeArray($this->_cookie, array('id'=>$this->sid(),'ip'=>IP,'t'=>b::utctime())));        
        }
        
    }
    
    public function delete() {
        ($this->_dao ? $this->_dao->delete() : false);    
        b::cookie()->set($this->_cname, false, b::utctime());        
    }

    // verify
    public function verify() {
    
        // make sure we've started
        $this->start();
                
        // if it's no loaded we know right away
        if (!$this->loaded()) { return false; }
    
        // make sure everything mataces
        if ($this->token == p('tok', false, $this->_cookie) AND b::account()->loaded() AND b::account()->id == p('a', false, $this->_cookie) AND b::account()->loaded()) {
            return true;
        }
        else {
            return false;
        }
    
    }

    // 
    public function login($args) {
    
        // suer name pass
        $e = strtolower(p('email', false, $args));
        $u = strtolower(p('username', false, $args));
        $p = p('password', false, $args);
    
        // password no crypt
        if (p('encrypted', false, $args) === false) { $p = b::crypt($p, b::_("salt")); }
    
        // try getting the account
        $a = b::account()->factory()->get(($u ? 'username' : 'email'), ($u ? $u : $e));
        
        // resp
        $resp = new \StdClass;
        
        // nope
        if (!$a->loaded()) {
            $resp->success = false;
            $resp->message = "Invalid account information";
            $resp->code = 404;
            return $resp;
        }
        
        // password match
        if ($a->password != $p) {
            $resp->success = false;
            $resp->message = "Invalid account information";
            $resp->code = 403;
            return $resp;
        }
        
        // set the account
        b::account()->setAccount($a);
    
        // all good 
        // start a session with their account
        $s = b::session();
        
        // destory what we have
        $s->delete();
        
        // add some data
        $s->token = md5(b::crypt($a->password));
        
        // reset our cookie with some session info
        $this->_cookie['tok'] = $s->token;
        $this->_cookie['a'] = $a->id;        
    
        // save
        $s->save();
        
        // resp
        $resp->success = true;
        $resp->session = $s;
        $resp->account = $a;
    
        // return the session
        return $resp;
    
    }
    
    // logout
    public function logout() {
    
    }


}

