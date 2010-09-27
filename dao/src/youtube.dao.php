<?php

// namespace 
namespace Dao;

// services
require_once 'Services/YouTube.php';

// youtube
class youtube extends Webservice {

	// do it
	protected $host = "gdata.youtube.com";
	protected $port = "80";

	public function get($type, $id) {
	
		// if id has http
		if ( strpos($id, 'http') !== false ) {
	
			// get the youtube 
			preg_match("#watch\?v=([a-zA-Z0-9]+)#i", trim($id), $m);

			// reset
			$id = $m[1];
			
		}
		
		// get it 
		$resp = $this->sendRequest('feeds/api/videos/'.$id,array('alt' => 'jsonc', 'v' => '2'));	

		// set 
		$this->set($resp['data']);
	
	}
	

}


?>