<?php

namespace Dao;

abstract class Mongo extends \Dao {

	// dbh
	protected $dbh = false;

	// privte info
	private $_host = false;
	private $_port = false;
	private $_db = false;
	private $_user = false;
	private $_pass = false;

	// normaize
	public function set($data) {
	
		// swithc out id 
		if ( isset($data['_id']) ) {
			$data['id'] = $data['_id'];
		}	
	
		// parent normalize
		parent::set($data);
	
	}

	// normaize
	public function normalize() {

		// parent normalize
		$data = parent::normalize();
		
		// swithc out id 
		if ( isset($data['id']) ) {
			$data['_id'] = $data['id'];
			unset($data['id']);
		}
		
		// give back
		return $data;
	
	}

	private function _connect() {

		// already connected
		if ( $this->dbh ) { return; }

		// get some
		$this->_host = \Config::get('mongo/host');
		$this->_port = \Config::get('mongo/port');
		$this->_db = \Config::get('mongo/db');
		$this->_user = \Config::get('mongo/user');
		$this->_pass = \Config::get('mongo/pass');

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
		catch ( \MongoConnectionException $e ) { die( $e->getMessage() ); }

	}

	public function setDb($name) {
		$this->_db = $name;
	}

	public function query($collection, $query, $args=array()) {

		// try connecting
		$this->_connect();

		// sth
		$db = $this->dbh->{$this->_db};

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
			$resp[] = $sth->getNext();
		}

		// return a response
		return new MongoResponse($resp,$sth);

	}

	public function count($collection,$query=array()) {

		// do it 
		$this->_connect();

		// sth
		$db = $this->dbh->{$this->_db};

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
		return $resp->item('first');

	}

	public function insert($collection,$data,$safe=false) {

		// try connecting
		$this->_connect();	

		// sth
		$db = $this->dbh->{$this->_db};

		// sth
		$sth = $db->{$collection};		

		// insert
		return $sth->insert($data,$safe);

	} 

	public function update($collection,$query,$data,$opts=array()) {

		// try connecting
		$this->_connect();	

		// sth
		$db = $this->dbh->{$this->_db};

		// sth
		$sth = $db->{$collection};			

		// run it
		$r = $sth->update($query, $data, $opts);
		
		// return
		return $r;

	}

	public function delete($collection,$query,$opts=array()) {

		// try connecting
		$this->_connect();	

		// sth
		$db = $this->dbh->{$this->_db};

		// sth
		$sth = $db->{$collection};			

		// run it
		return $sth->remove($query,$opts);	

	}

}

class MongoResponse extends \Dao implements \Iterator {

	public function __construct($items,$cur) {

		// set items
		$this->_items = $items;

		// set pager
		$this->setPager($cur->count(),1,1);

	}

}

?>