<?php

namespace bolt\browser;
use \b;

// view
b::plug('controller', '\bolt\browser\controllerFactory');

// source
class controllerFactory extends \bolt\plugin {

    // type is singleton
    // since this is really a plugin dispatch
    public static $TYPE = "factory";

    // factory
    public static function factory($class='\bolt\browser\controller') {

        // lets do it
        return new $class();

    }

}

interface iController {

}

class controller implements iController {

    private $_guid;
    private $_layout;
    private $_params;
    private $_content;
    private $_accept;
    private $_fromInit = false;

    protected $templateDir = false;

    public function __construct() {
        $this->_guid = uniqid();
        $this->_params = b::bucket();
        $this->_fromInit = $this->init();

    }

    public function init() {}

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
                        ->setFile($this->templateDir."/".$layout)
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
    public function setStatus($status) {
        b::response()->setStatus($status);
        return $this;
    }

    // run
    public function run() {

        // check
        if ($this->_fromInit AND b::isInterfaceOf($this->_fromInit, '\bolt\browser\iController')) {
            return $this->_fromInit;
        }

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
        else {
            return $this;
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
        if (is_string($resp)) {
            $this->setContent($resp);
        }

        // me
        return $resp;

    }

    public function renderTemplate($file, $vars=array(), $render='mustache') {
        $this->setContent(b::render(array(
                'render' => $render,
                'file' => $this->templateDir."/".$file,
                'controller' => $this,
                'vars' => $vars
            )));
        return $this;
    }

    public function renderString($str, $vars=array(), $render='mustache') {
        $this->setContent(b::render(array(
                'render' => $render,
                'string' => $str,
                'controller' => $this,
                'vars' => $vars
            )));
        return $this;
    }

    // render
    public function render($view) {

        // string
        if (is_string($view) AND class_exists($view, true)) {
            $view = b::view($view);
        }

        // make sure view implements bolt\browser\view
        if (!b::isInterfaceOf($view, '\bolt\browser\iView')) {
            return false;
        }

        // set our view
        return $view
                ->setController($this)
                ->render();

    }

    // get content
    public function getContent() {
        return $this->_content;
    }

    public function setContent($content) {
        if (b::isInterfaceOf($content, '\bolt\browser\iView')) {
            $content = $this->render($content);
        }
        $this->_content = $content;
        return $this;
    }

}