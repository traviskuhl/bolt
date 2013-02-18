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
    public static function factory() {

        // lets do it
        return new view();

    }

}

interface iView {

}


// the view
class view implements iView {

    // some things we're going to need
    private $_content = false;
    private $_data = array();
    private $_controller;
    private $_file = false;
    private $_params;

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

    public function render() {
        if ($this->_file !== false AND file_exists($this->_file)) {
            $this->setContent(file_get_contents($this->_file));
        }
        else {
        }
        return $this;
    }

}