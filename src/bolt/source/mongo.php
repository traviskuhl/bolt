<?php

// namespace me
namespace bolt\source;
use \b as b;

// plugin our global instance directly to bolt
b::plug('mongo', '\bolt\source\mongo');

// plugin to instance source factory
b::source()->plug('mongo', '\bolt\source\mongoi');

// mongo
class mongo extends \bolt\plugin\singleton {

    private $instance = false;

    public function __construct($args=array()) {
        $this->instance = b::source()->mongo(b::settings('project.mongo')->asArray());
    }

    // call it
    public function __call($name, $args) {
        return call_user_func_array(array($this->instance, $name), $args);
    }

}


class mongoi extends \bolt\plugin\factory {

	// dbh
	private $dbh = false;

	private $_db = false;
	private $config = array();
	private $grid = false;

	public function __construct($config) {
		$this->config = $config;
	}

	private function _connect() {

		// already connected
		if ( $this->dbh ) { return; }

		// get some
		$this->_host = b::param('host', false, $this->config);
		$this->_port = b::param('port', 27017, $this->config);
		$this->_db = ($this->_db ? $this->_db : b::param('db', false, $this->config));
		$this->_user = b::param('user', false, $this->config);
		$this->_pass = b::param('pass', false, $this->config);


		// try to connect
		try {

			// set dbh
			if ( $this->_user != false AND $this->_pass != false ) {
				$this->dbh = new \Mongo("mongodb://{$this->_user}:{$this->_pass}@{$this->_host}:{$this->_port}");
			}
			else {
				$this->dbh = new \Mongo("mongodb://{$this->_host}:{$this->_port}");
			}

		}
		catch ( \MongoConnectionException $e ) { header("Content-Type:text/html", true, 500); error_log($e->getMessage()); die("database connect fail"); }

	}

    public function __call($name, $args) {
        return call_user_func_array(array($this->getHandle()->{$this->_db}, $name), $args);
    }

    public function __get($name) {
        return $this->getHandle()->{$this->_db}->{$name};
    }

	public function setDb($name) {
		$this->config += array('db'=>$name);
		$this->_db = $name;
		if ($this->dbh) {
		  $this->selectDB($name);
		}
	}

	public function getDb() {
		return $this->_db;
	}

	public function getHandle() {
        $this->_connect();
		return $this->dbh;
	}

    public function getGridFS($db='fs', $prefix="fs") {
        if ($this->grid) {
            return $this->grid;
        }

        $this->_connect();

        // grid
        $this->grid = new \MongoGridFS($this->dbh->selectDB($db), $prefix);
        return $this->grid;

    }

	public function query($collection, $query, $args=array()) {

		// try connecting
		$this->_connect();

		// different db
		$_db = (isset($args['db']) ? $args['db'] : $this->_db);

		// sth
		$db = $this->dbh->{$_db};

		// sth
		$col = $db->{$collection};

		// id => _id
		if ( isset($query['id']) ) {

			// query _id
			$query['_id'] = $query['id'];

			// unset
			unset($query['id']);

		}

		// find
		$sth = $col->find($query);

		// fields
		if ( isset($args['fields']) ) {
			$sth->fields($args['fields']);
		}

		// limit
		if ( isset($args['per']) ) {
			$sth->limit($args['per']);
		}

		// skip
		if( isset($args['start']) ) {
			$sth->skip($args['skip']);
		}

		// sort
		if ( isset($args['sort']) ) {
			$sth->sort($args['sort']);
		}

		// resp
		$resp = array();

		// get them
		while ( $sth->hasNext() ) {
			$row = $sth->getNext();
			$row['id'] = $row['_id'];
			$resp[] = $row;
		}

		// return a response
		return $resp;

	}

	public function count($collection,$query=array(),$args=array()) {

		// do it
		$this->_connect();

		// different db
		$_db = (isset($args['db']) ? $args['db'] : $this->_db);

		// sth
		$db = $this->dbh->{$_db};

		// sth
		$sth = $db->{$collection};

		// reutrn it
		return $sth->count($query);

	}

	public function row($collection,$query,$args=array()) {

		// try connecting
		$this->_connect();

		// send to query
		$args['per'] = 1;

		// get them
		$resp = $this->query($collection,$query,$args);

		// return the first one
		return array_shift($resp);

	}

	public function insert($collection, $data, $safe=false, $args=array()) {

		// try connecting
		$this->_connect();

		// different db
		$_db = (isset($args['db']) ? $args['db'] : $this->_db);

		// sth
		$db = $this->dbh->{$_db};

		// sth
		$sth = $db->{$collection};

		// insert
		try {
			$resp = $sth->insert($data,$safe);
		}
		catch(\MongoCursorException $e) {
			throw $e; return;
		}

	}

	public function update($collection, $query, $data, $opts=array(), $args=array()) {

		// try connecting
		$this->_connect();

		// different db
		$_db = (isset($args['db']) ? $args['db'] : $this->_db);

		// sth
		$db = $this->dbh->{$_db};

		// sth
		$sth = $db->{$collection};

		// run it
		$r = $sth->update($query, $data, $opts);

		// return
		return $r;

	}

	public function delete($collection,$query,$opts=array(),$args=array()) {

		// try connecting
		$this->_connect();

		// different db
		$_db = (isset($args['db']) ? $args['db'] : $this->_db);

		// sth
		$db = $this->dbh->{$_db};

		// sth
		$sth = $db->{$collection};

		// run it
		return $sth->remove($query,$opts);

	}

}