<?php

namespace bolt\browser;
use \b;

// plug into b::response
b::plug('response', '\bolt\browser\response');

/**
 * browser response
 * @extends \bolt\plugin
 *
 */
class response extends \bolt\plugin {

	// we're a singleton
    public static $TYPE = 'singleton';

    private $_headers;
    private $_status = 200;
    private $_content = false;
    private $_data = array();
    private $_responseType = 'html';
    private $_contentType = 'text/plain';

    /**
     * construct a response
     *
     * @return self
     */
	public function __construct() {
		$this->_headers = b::bucket();
	}

    /**
     * MAGIC return params, where:
     *     status -> _status
     *     accept -> _accept
     *     contentType -> _contentType
     *     headers -> _headers bucket
     *
     * @param $name
     * @return mixed
     */
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

    /**
     * MAGIC set variables
     *
     * @param $name
     * @param $value
     * @return self
     */
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

    /**
     * get response content type
     *
     * @return self
     */
    public function getResponseType() {
        return $this->_responseType;
    }

    /**
     * set response content type
     *
     * @param $type content type string
     * @return self
     */
    public function setResponseType($type) {
        $this->_responseType = $type;
        return $this;
    }

    public function setContentType($type) {
        $this->_contentType = $type;
        return $this;
    }

    public function getContentType() {
        return $this->_contentType;
    }

    /**
     * get response headers
     *
     * @return headers bucket
     */
	public function getHeaders() {
		return $this->_headers;
	}

    /**
     * get the response status
     *
     * @return int response status
     */
	public function getStatus() {
		return $this->_status;
	}

    /**
     * set the response status (cast as int)
     *
     * @param $status
     * @return self
     */
	public function setStatus($status) {
		$this->_status = (int)$status;
        if ($this->_status === 0) { $this->_status = 500; }
		return $this;
	}

    /**
     * set response data
     *
     * @param $data
     * @return self
     */
    public function setData($data) {
        $this->_data = $data;
        return $this;
    }

    /**
     * get response data
     *
     * @return response data
     */
    public function getData() {
        return $this->_data;
    }


    /**
     * get the plguin that can response to request
     *
     * @return response plugin
     */
    public function getOutputHandler($rType) {


        // loop through all our plugins
        // to figure out which render to use
        foreach ($this->getPlugins() as $plug => $class) {
            if ($plug == $rType) {
                return $this->call($plug);
            }
        }

        // plain
        return false;

    }

    /**
     * execute the response
     *
     * @return string response
     */
	public function run() {

        // before
        $this->fire('before');

        $rType = $this->_responseType;

        // handler
        $handler = $this->getOutputHandler($rType);

        $content = $this->_content;
        $status = $this->_status;
        $data = $this->_data;
        $type = $this->_contentType;


        // is there a handler
        if ($handler) {

            // set some things for the handler
            $handler
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
