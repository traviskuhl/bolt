<?php

namespace bolt\browser;
use \b;

// view
b::plug('view', '\bolt\browser\viewFactory');

// source
class viewFactory extends \bolt\plugin {

    // type is singleton
    // since this is really a plugin dispatch
    public static $TYPE = "factory";

    // factory
    public static function factory($class='\bolt\browser\view') {
        return new $class();
    }

}

interface iView {

}


// the view
class view implements iView {

    // some things we're going to need
    private $_guid = false;
    private $_content = false;
    private $_data = array();
    private $_controller;
    private $_file = false;
    private $_render = 'handlebars';

    // bucketproxy needs to find
    protected $_params;

    // function
    final public function __construct($params=array()) {
        $this->_guid = uniqid();
        $this->_params = b::bucket($params);

        // init
        $this->init();

    }

    public function init() {}
    public function build() {}
    public function beforeRender() {}
    public function afterRender() {}

    public function __get($name) {
        if ($name == 'params') {
            return $this->_params;
        }
        return $this->_params->get($name);
    }
    public function __set($name, $value) {
        $this->_params->set($name, $value);
    }

    public function getGuid() {
        return $this->_guid;
    }

    public function setParams($params) {
        if (b::isInterfaceOf($params, '\bolt\bucket')) {
            $this->_params = $params;
        }
        else {
            $this->_params->set($params);
        }
        return $this;
    }

    public function setController($controller) {
        $this->_controller = $controller;
        return $this;
    }

    public function getContent($wrap=false) {
        return $this->_content;
    }

    public function setContent($content) {
        $this->_content = $content;
        return $this;
    }

    public function setData($data) {
        $this->_data = $data;
        return $this;
    }
    public function getData() {
        return $this->_data;
    }


    public function setFile($file) {
        $this->_file = $file;
        return $this;
    }

    final public function render($args=array()) {

        // call build
        call_user_func_array(array($this, 'build'), $args);

        // before render
        call_user_func(array($this,'beforeRender'));

        // add our view to the vars
        $this->_params->self = $this;

        if ($this->_file !== false) {
            $this->setContent(b::render(array(
                'render' => $this->_render,
                'file' => $this->_file,
                'controller' => $this->_controller,
                'vars' => $this->_params
            )));
        }
        else if ($this->_render) {
            $this->setContent(b::render(array(
                'render' => $this->_render,
                'string' => $this->_content,
                'controller' => $this->_controller,
                'vars' => $this->_params
            )));
        }

        // after render
        call_user_func(array($this,'afterRender'));


        return $this->getContent();
    }

}