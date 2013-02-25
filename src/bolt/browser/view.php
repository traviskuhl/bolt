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

        // lets do it
        return new $class();

    }

}

interface iView {

}


// the view
class view extends \bolt\bucket\proxy implements iView {

    protected $bucketProxy = '_params';

    // some things we're going to need
    private $_guid = false;
    private $_content = false;
    private $_data = array();
    private $_controller;
    private $_file = false;
    private $_render = 'mustache';

    // bucketproxy needs to find
    protected $_params;

    // function
    public function __construct($params=array()) {
        $this->_guid = uniqid();
        $this->_params = b::bucket($params);
    }


    public function setParams($params) {
        $this->_params->set($params);
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

    public function setFile($file) {
        if (stripos($file, '.template.php') === false) {
            $file .= '.template.php';
        }
        if (!file_exists($file)) {
            $file = b::config()->getValue("views")."/".$file;
        }

        $this->_file = $file;
        return $this;
    }

    public function render($vars=array()) {

        // loop throguh local params
        foreach ($this->_params as $key => $param)  {
            if (!array_key_exists($key, $vars)) {
                $vars[$key] = $param;
            }
        }
        if ($this->_controller) {
            foreach ($this->_controller->getParams() as $key => $param)  {
                if (!array_key_exists($key, $vars)) {
                    $vars[$key] = $param;
                }
            }
        }

        if ($this->_file !== false AND file_exists($this->_file)) {
            $this->setContent(b::render(array(
                'render' => $this->_render,
                'file' => $this->_file,
                'view' => $this,
                'controller' => $this->_controller,
                'vars' => $vars
            )));
        }
        else {
            $this->setContent(b::render(array(
                'render' => $this->_render,
                'string' => $this->_content,
                'view' => $this,
                'controller' => $this->_controller,
                'vars' => $vars
            )));
        }
        return $this;
    }

}