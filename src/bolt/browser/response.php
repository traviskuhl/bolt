<?php

namespace bolt\browser;
use \b;

// plug into b::response
b::plug('response', '\bolt\browser\response');

////////////////////////////////////////////////////////////////////
/// @brief browser response
/// @extends \bolt\plugin
///
////////////////////////////////////////////////////////////////////
class response extends \bolt\plugin {

	// we're a singleton
    public static $TYPE = 'singleton';

	private $_controller = false;
    private $_headers;
    private $_status = 200;
    private $_contentType = false;

    ////////////////////////////////////////////////////////////////////
    /// @brief construct a response
    ///
    /// @return self
    ////////////////////////////////////////////////////////////////////
	public function __construct() {
		$this->_headers = b::bucket();
	}

    ////////////////////////////////////////////////////////////////////
    /// @brief MAGIC return params, where:
    ///     status -> _status
    ///     accept -> _accept
    ///     contentType -> _contentType
    ///     headers -> _headers bucket
    ///
    /// @param $name
    /// @return mixed
    ////////////////////////////////////////////////////////////////////
    public function __get($name) {
        switch($name) {
            case 'status':
                return $this->_status;
            case 'contentType':
                return $this->_contentType;
            case 'controller':
                return $this->_controller;
            case 'headers':
                return $this->_headers;
        };
        return false;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief MAGIC set variables
    ///
    /// @param $name
    /// @param $value
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function __set($name, $value) {
        switch($name) {
            case 'status':
                return $this->_status = (int)$value;
            case 'contentType':
                $this->_contentType = $value;
        };
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get response content type
    ///
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function getContentType() {
        return $this->_contentType;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief set response content type
    ///
    /// @param $type content type string
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function setContentType($type) {
        $this->_contentType = $type;
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get response headers
    ///
    /// @return headers bucket
    ////////////////////////////////////////////////////////////////////
	public function getHeaders() {
		return $this->_headers;
	}

    ////////////////////////////////////////////////////////////////////
    /// @brief get the response status
    ///
    /// @return int response status
    ////////////////////////////////////////////////////////////////////
	public function getStatus() {
		return $this->_status;
	}

    ////////////////////////////////////////////////////////////////////
    /// @brief set the response status (cast as int)
    ///
    /// @param $status
    /// @return self
    ////////////////////////////////////////////////////////////////////
	public function setStatus($status) {
		$this->_status = (int)$status;
        if ($this->_status === 0) { $this->_status = 500; }
		return $this;
	}

    ////////////////////////////////////////////////////////////////////
    /// @brief get response controller. must implement \bolt\browser\iController
    /// @see \bolt\browser\controller
    ///
    /// @return response controller object
    ////////////////////////////////////////////////////////////////////
    public function getController() {
        return $this->_controller;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief set response controller. must implement \bolt\browser\iController
    /// @see \bolt\browser\controller
    ///
    /// @param $controller
    /// @return self;
    ////////////////////////////////////////////////////////////////////
    public function setController($controller) {
        if (!b::isInterfaceOf($controller, '\bolt\browser\iController')) {
            return false;
        }
        $this->_controller = $controller;
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get the plguin that can response to request
    ///
    /// @return response plugin
    ////////////////////////////////////////////////////////////////////
    public function getOutputHandler() {

        // our controller
        $cont = $this->_controller;

        // content type
        if ($this->_contentType === false) {
            $this->_contentType = b::request()->getAccept();
        }

        $map = array();

        // loop through all our plugins
        // to figure out which render to use
        foreach ($this->getPlugins() as $plug => $class) {
            foreach ($class::$contentType as $weight => $str) {
                $map[] = array($weight, $str, $plug);
            }
        }

        // sort renders by weight
        uasort($map, function($a,$b){
            if ($a[0] == $b[0]) {
                return 0;
            }
            return ($a[0] > $b[0]) ? -1 : 1;
        });

        // plug
        $plug = "html";

        // loop it
        foreach ($map as $item) {
            if ($item[1] == $this->_contentType) {
                $plug = $item[2]; break;
            }
        }

        // get our
        return $this->call($plug);

    }

    ////////////////////////////////////////////////////////////////////
    /// @brief execute the response
    ///
    /// @return string response
    ////////////////////////////////////////////////////////////////////
	public function run() {

        // handler
        $handler = $this->getOutputHandler();

        // before
        $this->fire('before');

        // type
        $type = $this->_contentType;

        // print a content type
        if (!headers_sent()) {

            header("Content-Type: {$type}", true, $this->getStatus());

        	// print all headers
            $this->_headers->map(function($name, $value){
            	header("$name: $value");
            });

        }

        // resp
        $resp = $handler->getContent($this->_controller);

        // after
        $this->fire('after', array('resp' => $resp));

        // respond
        return $resp;

	}

}