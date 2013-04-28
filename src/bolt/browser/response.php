<?php

namespace bolt\browser;
use \b;

b::plug('response', '\bolt\browser\response');

class response extends \bolt\plugin {

	// we're a singleton
    public static $TYPE = 'singleton';

	private $_controller;
    private $_headers;
    private $_status = 200;
    private $_accept = false;
    private $_contentType = false;


	public function __construct() {
		$this->_headers = b::bucket();
	}

    public function __get($name) {
        switch($name) {
            case 'headers':
                return $this->_headers;
        };
    }

    public function __set($name, $value) {
        if ($name == 'status') {
            $this->_status = (int)$value;
        }
        else if ($name == 'accept') {
            $this->_accept = $value;
        }
        else if ($name == 'controller') {
            $this->_controller = $value;
        }
        return $this;
    }

    public function setContentType($type) {
        $this->_contentType = $type;
        return $this;
    }

	public function getHeaders() {
		return $this->_headers;
	}

	public function getStatus() {
		return $this->_status;
	}
	public function setStatus($status) {
		$this->_status = $status;
		return $this;
	}

    public function getAccept() {
        return $this->_accept;
    }
    public function setAccept($accept) {
        $this->_accept = $accept;
        return $this;
    }

    public function getController() {
        return $this->_controller;
    }
    public function setController($controller) {
        $this->_controller = $controller;
        return $this;
    }

    public function getOutputHandler() {

        // our controller
        $cont = $this->_controller;

        // figure out our accept
        $accept = $this->_accept;

            // does the controller have one
            if ($accept === false AND $cont->getAccept()) {
                $accept = $cont->getAccept();
            }

            // fall back to the request
            if ($accept === false AND b::request()->getAccept()) {
                $accept = b::request()->getAccept();
            }

            // still no we use text/plain
            if ($accept === false) {
                $accept = 'text/plain';
            }

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
            if ($item[1] == $accept) {
                $plug = $item[2]; break;
            }
        }

        // get our
        return $this->call($plug);

    }

	public function run() {

        // handler
        $handler = $this->getOutputHandler();

        // type
        $type = ($this->_contentType ?: $handler->contentType);

        // print a content type
        header("Content-Type: {$type}", true, $this->getStatus());

    	// print all headers
        $this->_headers->map(function($name, $value){
        	header("$name: $value");
        });

        // resp
        $resp = $handler->getContent($this->_controller);

        // respond
        exit($resp);

	}

}