<?php

namespace bolt\browser;
use \b;

b::plug('request', '\bolt\browser\request');


class request extends \bolt\plugin\singleton {

	private $_accept = array();
	private $_method = false;
	private $_get;
	private $_post;		
	private $_request;	
	private $_headers;
	private $_input = "";

	public function __construct() {

		// get from env
		$this->_method = p("REQUEST_METHOD", "GET", $_SERVER);
		$this->_accept = p('HTTP_ACCEPT', false, $_SERVER);

		// create a bucket of our params
		$this->_get = b::bucket($_GET);
		$this->_post = b::bucket($_POST);
		$this->_request = b::bucket($_REQUEST);
		$this->_headers = b::bucket(array_change_key_case(getallheaders()));
		$this->_input = file_get_contents("php://input");

	}

	public function execute() {

		// fire lets run our router to figure out what
		// route we need to take
		$route = b::route('match', bPath);

			// no route just die right now
			if (!$route) {
				die(" YOU SHOULDN'T SEE THIS. EMAIL ME NOW! ");
			}

		// create our view
		$view = new $route['class']($route['args']);

		// now create a response 
		b::response()->setView($view)->respond();

	}

	// shortcuts
	public function __get($name) {
		switch($name) {
			case 'params':
				return $this->getParams();
			case 'headers':
				return $this->getHeaders();
			case 'input':
				return $this->getInput();
			default:
				return false;
		};		
	}

	public function getParams($type='request') {		
		switch($type) {
			case 'get': return $this->_get;
			case 'post': return $this->_post;
			default: return $this->_request;
		};
	}	
	public function getHeaders() {
		return $this->_headers;
	}
	public function getInput() {
		return $this->_input;
	}	




	public function getMethod() {
		return $this->_method;
	}
	public function setMethod($method){
		$this->_method = $method;
		return $this;
	}

	public function getAccept() {
		return $this->_accept;
	}
	public function setAccept($accept) {
		$this->_accept = (array)$accept;
		return $this;
	}



}