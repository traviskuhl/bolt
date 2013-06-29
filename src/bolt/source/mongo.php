<?php

// namespace me
namespace bolt\source;
use \b as b;

class mongo extends base {

	const NAME = "mongo";

	// dbh
	private $_dbh = false;

	private $_db = false;
	private $_config = array();
	private $grid = false;

	public function __construct($config) {
		$this->_config = $config;
	}

	private function _connect() {

		// already connected
		if ( $this->_dbh ) { return; }

		// get some
		$this->_host = b::param('host', false, $this->_config);
		$this->_port = b::param('port', 27017, $this->_config);
		$this->_db = ($this->_db ? $this->_db : b::param('db', false, $this->_config));
		$this->_user = b::param('username', false, $this->_config);
		$this->_pass = b::param('password', false, $this->_config);


		// try to connect
		try {

			// set dbh
			if ( $this->_user != false AND $this->_pass != false ) {
				$this->_dbh = new \Mongo("mongodb://{$this->_user}:{$this->_pass}@{$this->_host}:{$this->_port}");
			}
			else {
				$this->_dbh = new \Mongo("mongodb://{$this->_host}:{$this->_port}");
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
		if ($this->_dbh) {
		  $this->selectDB($name);
		}
	}

	public function getDb() {
		return $this->_db;
	}

	public function getHandle() {
        $this->_connect();
		return $this->_dbh;
	}

    public function getGridFS($db='fs', $prefix="fs") {
        if ($this->grid) {
            return $this->grid;
        }

        $this->_connect();

        // grid
        $this->grid = new \MongoGridFS($this->_dbh->selectDB($db), $prefix);
        return $this->grid;

    }




	public function query($collection, $query, $args=array()) {

		// try connecting
		$this->_connect();

		// different db
		$_db = (isset($args['db']) ? $args['db'] : $this->_db);

		// sth
		$db = $this->_dbh->{$_db};

		// sth
		$col = $db->{$collection};

		// id => _id
		if ( isset($query['id']) ) {

			// query _id
            if (strlen($query['id']) >= 24) {
                $query['_id'] = new \MongoId($query['id']);
            }
            else {
                return \bolt\bucket::a(array());
            }

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
			$row['id'] = (string)$row['_id']; unset($row['_id']);
			$resp[] = $row;
		}

		// return a response
		return \bolt\bucket::a($resp);

	}

	public function count($collection,$query=array(),$args=array()) {

		// do it
		$this->_connect();

		// different db
		$_db = (isset($args['db']) ? $args['db'] : $this->_db);

		// sth
		$db = $this->_dbh->{$_db};

		// sth
		$sth = $db->{$collection};

		// reutrn it
		return $sth->count($query);

	}


	public function insert($collection, $data, $safe=false, $args=array()) {

		// try connecting
		$this->_connect();

		// different db
		$_db = (isset($args['db']) ? $args['db'] : $this->_db);

		// sth
		$db = $this->_dbh->{$_db};

		// sth
		$sth = $db->{$collection};


		if (isset($data['id']) AND $data['id']) {
			$data['_id'] = $data['id'];
		}

		unset($data['id']);

		// insert
		try {
			$resp = $sth->insert($data,$safe);
		}
		catch(\MongoCursorException $e) {
			throw $e; return;
		}

		$data['id'] = (string)$data['_id'];
		unset($data['_id']);

		return array($resp, $data);

	}

	public function update($collection, $query, $data, $opts=array(), $args=array()) {

		// try connecting
		$this->_connect();

		// different db
		$_db = (isset($args['db']) ? $args['db'] : $this->_db);

		// sth
		$db = $this->_dbh->{$_db};

		// sth
		$sth = $db->{$collection};


		if (isset($data['id'])) {
			$data['_id'] = $data['id'];
			unset($data['id']);
		}

		// run it
		$r = $sth->update($query, $data, $opts);

		// return
		return array($r, $data);

	}

	public function delete($collection,$query,$opts=array(),$args=array()) {

		// try connecting
		$this->_connect();

		// different db
		$_db = (isset($args['db']) ? $args['db'] : $this->_db);

		// sth
		$db = $this->_dbh->{$_db};

		// sth
		$sth = $db->{$collection};

		// run it
		return $sth->remove($query,$opts);

	}

}