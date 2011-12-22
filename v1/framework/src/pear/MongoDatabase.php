<?php

class MongoDatabase {

	private static $instance = false;
	public static $_dbh = false;
	
	private function __construct(){
		$db = Config::get('mongo');			
		self::$_dbh = new MongoDatabaseInstance($db);
	}
	
	public static function singleton() {
	
		// already created
		if ( !self::$instance ) {
			$c = __CLASS__;
			self::$instance = new $c();
		}
		
		// give it 
		return self::$instance;
	
	}
	
	// pass allongs
	public function query() { return $this->call('query', func_get_args()); }
	public function row() { return $this->call('row', func_get_args()); }
	public function count() { return $this->call('count', func_get_args()); }	
	public function insert() { return $this->call('insert', func_get_args()); }	
	public function update() { return $this->call('update', func_get_args()); }	
	public function delete() { return $this->call('delete', func_get_args()); }	
	public function getGridFS() { return $this->call('getGridFS', func_get_args()); }
	
	// call it
	public function call($name, $args) {
		return call_user_func_array(array(self::$_dbh, $name), $args);
	}
		
	// static calls
	public static function __callStatic($name, $args) {
		self::singleton();
		if (method_exists(self::$_dbh, $name)) { 
			return call_user_func_array(array(self::$_dbh, $name), $args);
		}
	}


}


////////////////////////////////////
/// @breif database wrapper
////////////////////////////////////
class MongoDatabaseInstance {

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
		$this->_host = p('host', false, $this->config);
		$this->_port = p('port', false, $this->config);
		$this->_db = ($this->_db ? $this->_db : p('db', false, $this->config));
		$this->_user = p('user', false, $this->config);
		$this->_pass = p('pass', false, $this->config);

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
		$this->config += array('db'=>$name);
		$this->_db = $name;				
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
			$resp[] = $sth->getNext();
		}

		// return a response
		return new MongoResponse($resp,$sth);

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
		return $resp->item('first');

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

class MongoResponse extends \Dao implements \Iterator {

	public function __construct($items,$cur) {

		// set items
		$this->_items = $items;

		// set pager
		$this->setPager($cur->count(),1,1);

	}

}


?>