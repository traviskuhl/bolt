<?php

namespace dao;

class urlshort extends \Webservice {
	
	private $bitlyLogin = false;
	private $bitlyKey = false;
	
	public $data = array(
		'long'=>false,
		'short'=>false
	);
	
	public function __construct($args) {
				
		parent::__construct(array('host'=>'api.bit.ly'));
		
		if (isset($args['bitlyLogin']) AND isset($args['bitlyKey'])) { 
			$this->bitlyLogin = $args['bitlyLogin'];
			$this->bitlyKey = $args['bitlyKey'];
		} else { 
			return false;
		}
	
	}
	
	public function get($address) {
		
		// see if the url has been cached
		/*if ($cached = $this->cache->get($address,'urlshort')) { 
				
			$row = $cached; 
		
		// not cached so just do the webservice call
		} else { */
		
			$result = $this->sendRequest('v3/shorten?login='.$this->bitlyLogin.'&apiKey='.$this->bitlyKey.'&uri='.urlencode($address).'&format=json');
								
			if ($result['status_code'] == '200') { 
				$short = $result['data']['url'];
			} else { 
				$short = $address;
			}
											
			$row = array(
				'long'=>$address,
				'short'=>$short,
			);
						
			// expire
			$expire = time()+(60*60*3);
		
			// add their session to the cache
			//$this->cache->set($address,$row,$expire,'urlshort');
			
		//}
							
		// set
		return $row;
			
	}
	
	public function set($row) {
	
		$this->data = $row;
			
	}
		
	
}


?>