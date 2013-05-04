<?php

namespace bolt\browser;
use \b;

// plug view into bolt
b::plug('view', '\bolt\browser\viewFactory');

////////////////////////////////////////////////////////////////////
/// @brief factory generator for view class
/// @extends \bolt\plugin\singleton
///
////////////////////////////////////////////////////////////////////
class viewFactory extends \bolt\plugin {

    // type is singleton
    // since this is really a plugin dispatch
    public static $TYPE = "factory";

    ////////////////////////////////////////////////////////////////////
    /// @brief generate a view
    /// @static
    ///
    /// @param $class view class to generate
    /// @return view class
    ////////////////////////////////////////////////////////////////////
    public static function factory($class='\bolt\browser\view') {
        return new $class();
    }

}

interface iView {

}


////////////////////////////////////////////////////////////////////
/// @brief base view class
/// @implements iView
///
////////////////////////////////////////////////////////////////////
class view extends \bolt\event implements iView {

    // some things we're going to need
    private $_guid = false;
    private $_content = false;
    private $_data = array();
    private $_controller;
    private $_file = false;
    private $_render = 'handlebars';

    // bucketproxy needs to find
    protected $_params;

    ////////////////////////////////////////////////////////////////////
    /// @brief contruct a new view
    ///
    /// @param $params array of view params
    /// @return void
    ////////////////////////////////////////////////////////////////////
    final public function __construct($params=array()) {
        $this->_guid = uniqid();
        $this->_params = b::bucket($params);

        // init
        $this->init();

    }

    ////////////////////////////////////////////////////////////////////
    /// @brief called by __construct
    ///
    /// @return void
    ////////////////////////////////////////////////////////////////////
    protected function init() {}

    ////////////////////////////////////////////////////////////////////
    /// @brief called by render to set content
    ///
    /// @return void
    ////////////////////////////////////////////////////////////////////
    protected function build() {}

    ////////////////////////////////////////////////////////////////////
    /// @brief called by render before build
    ///
    /// @return void
    ////////////////////////////////////////////////////////////////////
    protected function before() {}

    ////////////////////////////////////////////////////////////////////
    /// @brief called by render after build and render
    ///
    /// @return void
    ////////////////////////////////////////////////////////////////////
    protected function after() {}

    ////////////////////////////////////////////////////////////////////
    /// @brief MAGIC get a param from params bucket
    /// @see self::getParam
    ///
    /// @param $name
    /// @return value
    ////////////////////////////////////////////////////////////////////
    public function __get($name) {
        return $this->getParam($name);
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief MAGIC set a param to params bucket
    /// @see bucket::set
    ///
    /// @param $name
    /// @param $value
    /// @return void
    ////////////////////////////////////////////////////////////////////
    public function __set($name, $value) {
        $this->_params->set($name, $value);
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get a param
    ///
    /// @param $name
    /// @return value
    ////////////////////////////////////////////////////////////////////
    public function getParam($name) {
        if ($name == 'params') {
            return $this->_params;
        }
        else if ($this->_params->exists($name)) {
            return $this->_params->get($name);
        }
        else if ($this->_controller AND $this->_controller->getParams()->exists($name)) {
            return $this->_controller->getParam($name);
        }
        return b::bucket();
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get params
    ///
    /// @return params bucket
    ////////////////////////////////////////////////////////////////////
    public function getParams() {
        return $this->_params;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief set params
    ///
    /// @param $params
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function setParams($params) {
        if (is_a($params, '\bolt\bucket') ) {
            $this->_params = $params;
        }
        else {
            $this->_params->set($params);
        }
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get the unique id of view
    ///
    /// @return string guid
    ////////////////////////////////////////////////////////////////////
    public function getGuid() {
        return $this->_guid;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief set the view controller
    ///
    /// @param $controller
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function setController($controller) {
        $this->_controller = $controller;
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get the view controller
    ///
    /// @return controller object
    ////////////////////////////////////////////////////////////////////
    public function getController() {
        return $this->_controller;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get view content
    ///
    /// @return view content
    ////////////////////////////////////////////////////////////////////
    public function getContent() {
        return $this->_content;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief set the view content
    ///
    /// @param $content view content
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function setContent($content) {
        $this->_content = $content;
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief set the view file
    ///
    /// @param $file path to file
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function setFile($file) {
        $this->_file = $file;
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get the view file
    ///
    /// @return view file
    ////////////////////////////////////////////////////////////////////
    public function getFile() {
        return $this->_file;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief set renderer
    ///
    /// @param $name render name
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function setRenderer($name) {
        $this->_render = $name;
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get renderer
    ///
    /// @return renderer
    ////////////////////////////////////////////////////////////////////
    public function getRenderer() {
        return $this->_render;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief render the given view
    ///
    /// @param $args array of argumnets
    /// @return content of view
    ////////////////////////////////////////////////////////////////////
    final public function render($args=array()) {

        // call build
        call_user_func_array(array($this, 'build'), $args);

        // before
        $this->fire('before');

        // before render
        call_user_func(array($this, 'before'));

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
        call_user_func(array($this,'after'));

        // after
        $this->fire('after');

        return $this->getContent();
    }

}