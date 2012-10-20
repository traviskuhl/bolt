<?php

namespace bolt\browser;
use \b;

b::plug('response', '\bolt\browser\response');

class response extends \bolt\plugin {

	// we're a singleton
    public static $TYPE = 'singleton';

	private $_view;
	private $_headers;
	private $_status = 200;

	public function __construct() {
		$this->_headers = b::bucket();
	}

	public function __get($name) {
		switch($name) {
			case 'headers':
				return $this->getHeaders();
			default:
				return false;
		};
	}

	public function getHeaders() {
		return $this->_headers;
	}

	public function setView(\bolt\view $view) {
		$this->_view = $view;
		return $this;
	}

	public function getStatus() {
		return $this->_status;
	}
	public function setStatus($status) {
		$this->_status = $status;
		return $this;
	}

	public function respond() {

		// execute our view
		$this->_view->execute();

		// what do we want to accept
		$req = b::request();
                       
        // accept
        $map = array();
        
        // loop through all our plugins 
        // to figure out which render to use 
        foreach ($this->getPlugins() as $plug => $class) {        
            foreach ($class::$accept as $weight => $str) {
                $map[] = array($weight, $str, $plug);
            }
        }
        
        // sort renders by weight
        uasort($map, function($a,$b){
            if ($a[0] == $b[0]) {
                return 0;
            }
            return ($a[0] < $b[0]) ? -1 : 1;        
        }); 
        
        // plug
        $plug = "html"; 
        
        // loop it 
        foreach ($map as $item) {
            if ($item[1] == b::request()->getAccept()) {
                $plug = $item[2]; break;
            }
        }
                
        // get our 
        $p = $this->call($plug);

        // print a content type
        $this->_headers->set('Content-Type', $p->contentType);
    
    	// print all headers
        $this->_headers->map(function($name, $value){
        	header("$name: $value");
        });

        // respond
        exit($p->getContent($this->_view));

	}

}