<?php

// namespace me
namespace bolt\source;
use \b;

class pdo extends base {

    const NAME = 'pdo';

    public function query($model, $query, $args=array()) {}

    public function row($model, $field, $value, $args=array()) {}

    public function insert($model, $data, $args=array()) {}

    public function update($model, $id, $data, $args=array()) {}

    public function delete($model, $id, $args=array()) {}

    public function count($model, $query, $args=array()) {}

}

// class pdo extends base {

//     const NAME = "pdo";

// 	// dbh
// 	private $dbh = false;
// 	private $config = array();

//     // construct
// 	public function __construct($config) {
// 		$this->config = $config;
// 	}

//     // connect
// 	private function _connect() {

// 		// already connected
// 		if ( $this->dbh ) { return; }

// 		// get some
//         $this->_dsn = p('dsn', false, $this->config);
// 		$this->_user = p('user', false, $this->config);
// 		$this->_pass = p('pass', false, $this->config);
// 		$this->_opts = p('opts', array(), $this->config);

// 		// try to connect
// 		try {
//             $this->dbh = new \PDO($this->_dsn, $this->_user, $this->_pass, $this->_opts);
// 		}
// 		catch ( \PDOException $e ) { header("Content-Type:text/html", true, 500); error_log($e->getMessage()); die("database connect fail"); }

// 	}

//     // pass to dbh
//     public function __call($name, $args) {
//         $this->_connect();
//         return call_user_func_array(array($this->dbh, $name), $args);
//     }

//     // get
//     public function __get($name) {
//         return (property_exists($this->dbh, $name) ? $this->dbh->$name : false);
//     }

// }