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

    private $_headers;
    private $_status = 200;
    private $_contentType = false;
    private $_content = false;
    private $_data = array();

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

    public function setContent($content) {
        $this->_content = $content;
        return $this;
    }

    public function getContent() {
        return $this->_content;
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
    /// @brief set response data
    ///
    /// @param $data
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function setData($data) {
        $this->_data = $data;
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get response data
    ///
    /// @return response data
    ////////////////////////////////////////////////////////////////////
    public function getData() {
        return $this->_data;
    }


    ////////////////////////////////////////////////////////////////////
    /// @brief get the plguin that can response to request
    ///
    /// @return response plugin
    ////////////////////////////////////////////////////////////////////
    public function getOutputHandler() {

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

        // before
        $this->fire('before');

        // handler
        $handler = $this->getOutputHandler();

        $content = $this->_content;
        $status = $this->_status;
        $data = $this->_data;
        $type = $this->_contentType;

        // is there a handler
        if ($handler) {

            // set some things for the handler
            $handler
                ->setContentType($this->_contentType)
                ->setStatus($this->_status)
                ->setData($this->_data)
                ->setContent($this->_content);

            $content = $handler->handle();

            $status = $handler->getStatus();
            $data = $handler->getData();
            $type = $handler->getContentType();

        }

        // print a content type
        if (!headers_sent()) {

            header("Content-Type: {$type}", true, $status);

        	// print all headers
            $this->_headers->map(function($name, $value){
            	header("$name: $value");
            });

        }

        // after
        $this->fire('after', array('resp' => $content));

        // respond
        return $content;

	}

}
