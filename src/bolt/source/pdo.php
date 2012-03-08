<?php

// namespace me
namespace bolt\source;
use \b;

// plugin our global instance directly to bolt
b::plug('pdo', '\bolt\source\pdo');

// plugin to instance source factory
b::source()->plug('pdo', '\bolt\source\pdoi');

// mongo
class pdo extends \bolt\plugin\singleton {

    private $instance = false;

    public function __construct($args=array()) {  
        $this->instance = b::source()->pdo(b::config()->pdo);
    }
    
    // call it
    public function __call($name, $args) {
        return call_user_func_array(array($this->instance, $name), $args);
    }

}

class pdoi extends \bolt\plugin\factory {

	// dbh
	private $dbh = false;	
	private $config = array();

    // construct
	public function __construct($config) {	 
		$this->config = $config;		
	}

    // connect
	private function _connect() {

		// already connected
		if ( $this->dbh ) { return; }

		// get some
        $this->_dsn = p('dsn', false, $this->config);
		$this->_user = p('user', false, $this->config);
		$this->_pass = p('pass', false, $this->config);
		$this->_opts = p('opts', array(), $this->config);

		// try to connect
		try { 
            $this->dbh = new \PDO($this->_dsn, $this->_user, $this->_pass, $this->_opts);
		}
		catch ( \PDOException $e ) { header("Content-Type:text/html", true, 500); error_log($e->getMessage()); die("database connect fail"); }

	}

    // pass to dbh
    public function __call($name, $args) {
        $this->_connect();
        return call_user_func_array(array($this->dbh, $name), $args);
    }
    
    // get 
    public function __get($name) {
        return (property_exists($this->dbh, $name) ? $this->dbh->$name : false);
    }

}