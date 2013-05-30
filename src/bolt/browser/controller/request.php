<?php

namespace bolt\browser\controller;
use \b;

class request extends \bolt\browser\controller {

    // render
    private $_content = false;
    private $_data = array();
    private $_properties = array();
    private $_hasRendered = false;
    private $_route = false;

    /**
     * set the route
     *
     * @param $route
     * @return self
     */
    public function setRoute($route) {
        $this->_route = $route;
        return $this;
    }

    /**
     * get the route for controller
     *
     * @return route
     */
    public function getRoute() {
        return $this->_route;
    }


    /**
     * get the accept header from b::request
     * @see \bolt\browser\request::getAccept
     *
     * @return accept header value
     */
    public function getAccept() {
        return b::request()->getAccept();
    }

    /**
     * set the accept header from b::request
     * @see \bolt\browser\request::getAccept
     *
     * @param $header
     * @return self
     */
    public function setAccept($header) {
        b::response()->setAccept($header);
        return $this;
    }

    /**
     * set response content type
     * @see \bolt\browser\response::setContentType
     *
     * @param $type
     * @return self
     */
    public function setContentType($type) {
        b::response()->setContentType($type);
        return $this;
    }

    /**
     * get response content type
     * @see \bolt\browser\response::setContentType
     *
     * @return content type
     */
    public function getContentType() {
        return b::response()->getContentType();
    }

    /**
     * get the response status in b::response
     * @see \bolt\browser\response::getStatus
     *
     * @return status
     */
    public function getStatus() {
        return b::response()->getStatus();
    }

    /**
     * set the response status in b::response
     * @see \bolt\browser\response::setStatus
     *
     * @param $status (int) http status
     * @return \bolt\bucket params
     */
    public function setStatus($status) {
        b::response()->setStatus($status);
        return $this;
    }

    /**
     * execute the controller
     *
     * @param $route route class
     * @return mixed response
     */
    public function run($route) {

        // check
        if ($this->_fromInit AND b::isInterfaceOf($this->_fromInit, '\bolt\browser\iController')) {
            return $this->_fromInit;
        }

        // get some stuff from the route
        $params = $route->getParams();
        $method = strtolower($route->getMethod());
        $action = $route->getAction();

        // figure out how we handle this request
        // order goes
        // 1. dispatch
        // 2. method+action
        // 3. action
        // 4. method
        // 5. get
        // 6. build

        if (method_exists($this, 'dispatch')) {
            $act = 'dispatch';
        }
        else if (method_exists($this, $method.$action)) {
            $act = $method.$action;
        }
        else if (method_exists($this, $action)) {
            $act = $action;
        }
        else if (method_exists($this, $method)) {
            $act = $method;
        }
        else if (method_exists($this, 'get')){
            $act = 'get';
        }
        else {
            $act = 'build';
        }

        // set our action
        $this->setAction($act);

        // execute render
        $this->render($params);

        // return me
        return $this;
    }



}