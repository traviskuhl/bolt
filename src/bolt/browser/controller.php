<?php

namespace bolt\browser;
use \b;

interface iController {

}

class controller implements iController {

    private $_guid;
    private $_layout;
    private $_params;
    private $_view;
    private $_content;
    private $_accept;

    public function __construct() {
        $this->_guid = uniqid();
        $this->_params = b::bucket();
    }


    public function __set($name, $value) {
        $this->_params->set($name, $value);
    }

    public function __get($name) {
        switch($name) {
            case 'request':
                return b::request();
            case 'response':
                return b::response();
            default:
              return $this->_params->get($name);
        };
    }

    public function getParams() {
        return $this->_params;
    }
    public function getGuid() {
        return $this->_guid;
    }

    public function setLayout($layout) {
        if (is_string($layout)) {
            $layout = b::view()
                        ->setFile($layout)
                        ->setController($this);
        }
        $this->_layout = $layout;
        return $this;
    }

    public function getLayout() {
        return $this->_layout;
    }
    public function hasLayout() {
        return $this->_layout;
    }

    public function getAccept() {
        return $this->_accept;
    }
    public function setAccept($accept) {
        $this->_accept = $accept;
        return $this;
    }

    // run
    public function run() {

        // lets figure out what method was request
        $method = strtolower(b::request()->getMethod());

        // figure out how we handle this request
        // order goes
        // 1. _dispatch
        // 2. method
        // 3. get

        if (method_exists($this, '_dispatch')) {
            $func = '_dispatch';
        }
        else if (method_exists($this, $method)) {
            $func = $method;
        }
        else if (method_exists($this, 'get')){
            $func = 'get';
        }

        // params from the request
        $params = b::request()->getParams();

        // reflect our method and add any
        // request params
        $m = new \ReflectionMethod($this, $func);

        // args we're going to send when we call
        $args = array();

        // method params
        if ($m->getNumberOfParameters() > 0) {
            foreach ($m->getParameters() as $i => $param) {
                $v = false;
                if ($params->exists($param->name)) {
                    $v = $params->getValue($param->name);
                }
                else {
                    $v = $params->getValue($i);
                }
                if ($v === false AND $param->isOptional()) {
                    $v = $param->getDefaultValue();
                }
                $args[] = $v;
            }
        }
        else {
            $args = $this->getParams()->asArray();
        }

        // go ahead an execute
        $resp = call_user_func_array(array($this, $func), $args);

        // if response is a view
        // render it
        if (is_object($resp) AND b::isInterfaceOf($reps, '\bolt\browser\iView')) {
            $this->render($resp);
        }
        else if (is_string($resp)) {
            $this->setContent($resp);
        }

        // me
        return $this;

    }

    public function renderTemplate($file, $params=array()) {
        $view = b::view()
                    ->setParams($params)
                    ->setFile($file);
        return $this->render($view);
    }

    public function renderString($str, $params) {
        $view = b::view()
                ->setContent($str)
                ->setParams($params);
        return $this->render($view);
    }

    // render
    public function render($view) {

        // make sure view implements bolt\browser\view
        if (!b::isInterfaceOf($view, '\bolt\browser\iView')) {
            return false;
        }

        // set our view
        $this->setContent(
            $view
                ->setController($this)
                ->render()
                ->getContent()
        );

    }

    // get content
    public function getContent() {
        return $this->_content;
    }

    public function setContent($content) {
        $this->_content = $content;
        return $this;
    }

}