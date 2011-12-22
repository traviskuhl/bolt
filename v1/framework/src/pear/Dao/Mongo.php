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

	public function __construct($type=false, $cfg=array()) {
	
		$this->dbh = \MongoDatabase::singleton();	
		
		// parent
		parent::__construct($type,$cfg);			
	
	}
	
	// passthrough
	public function __call($name, $args) {
		if (method_exists($this->dbh, $name)) {	
			return call_user_func_array(array($this->dbh, $name), $args);
		}
		else {
			return parent::__call($name, $args);
		}
	}

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



}


?>