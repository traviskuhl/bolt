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
    private $_file = false;
    private $_render = 'handlebars';
    private $_properties = array();
    private $_layout = array();
    private $_parent = false;

    // static
    protected $layout = false;

    // bucketproxy needs to find
    protected $_params;

    ////////////////////////////////////////////////////////////////////
    /// @brief contruct a new view
    ///
    /// @param $params array of view params
    /// @param $parent parent view
    /// @return void
    ////////////////////////////////////////////////////////////////////
    final public function __construct($params=array(), \bolt\browser\view $parent=null) {
        $this->_guid = uniqid();
        $this->_params = b::bucket($params);
        $this->_parent = $parent;

        // check if a layout property is set
        if ($this->layout) {
            $this->setLayout($this->layout);
        }

        // any properties
        $ref = new \ReflectionClass($this);

        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED) as $prop) {
            if (!$prop->isStatic()) {
                $this->_properties[] = $prop->getName();
            }
        }

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
    /// @brief set the parent view
    ///
    /// @param \bolt\browser\view $parent
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function setParent(\bolt\browser\view $parent) {
        $this->_parent = $parent;
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get the parent view
    ///
    /// @return parent view
    ////////////////////////////////////////////////////////////////////
    public function getParent() {
        return $this->_parent;
    }


    ////////////////////////////////////////////////////////////////////
    /// @brief MAGIC get a param from params bucket
    /// @see self::getParam
    ///
    /// @param $name
    /// @return value
    ////////////////////////////////////////////////////////////////////
    public function __get($name) {
        if ($name == 'params') {
            return $this->_params;
        }
        else if (array_key_exists($name, $this->_properties)) {
            return $this->{$name};
        }
        else if ($this->_parent->exists($name)) {
            return $this->_parent->getParam($name);
        }
        return false;
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
        $this->_properties[] = $name;
        $this->{$name} = $value;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief get a param
    ///
    /// @param $name
    /// @return value
    ////////////////////////////////////////////////////////////////////
    public function getParam($name, $default=null) {
        if (!$name) {
            return b::bucket();
        }
        else if ($name == 'params') {
            return $this->_params;
        }
        else if ($this->_params->exists($name)) {
            return $this->_params->get($name, $default);
        }
        else if ($this->_param AND $this->_param->exists($name)) {
            return $this->_param->getParam($name, $default);
        }
        else if (array_key_exists($name, $this->_properties)) {
            return $this->{$name};
        }
        return ($default === null ? b::bucket() : $default);
    }

    public function getParamValue($name, $default=null) {
        $val = $this->getParam($name, $default);
        return ($val ?: $default);
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

    public function exists($name) {
        if (in_array($name, $this->_properties)) {
            return true;
        }
        return $this->_params->exists($name);
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
    /// @brief set the layout view
    ///
    /// @param $layout (string|\bolt\browser\view) path to template or view object
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function setLayout($layout) {
        if (is_string($layout)) {
            $file = b::config()->getValue("project.templates")."/".$layout;
            $layout = b::view()
                        ->setFile($file)
                        ->setController($this);
        }
        $this->_layout = $layout;
        return $this;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief return layout view
    ///
    /// @return \bolt\browser\view
    ////////////////////////////////////////////////////////////////////
    public function getLayout() {
        return $this->_layout;
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief check if the controller has a layout view
    ///
    /// @return bool
    ////////////////////////////////////////////////////////////////////
    public function hasLayout() {
        return !($this->_layout === false);
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

        // globalize any args
        foreach ($args as $name => $value) {
            $this->{$name} = $value;
        }

        // build args
        $_args = array();

        // reflect
        $ref = new \ReflectionMethod($this, 'build');

        if ($ref->getNumberOfParameters() > 0) {
            foreach ($ref->getParameters() as $param) {
                $name = $param->getName();
                if (array_key_exists($name, $args)) {
                    $_args[] = $args['name'];
                }
                else if ($this->_params->exists($name)) {
                    $_args[] = $this->_params->getValue($name);
                }
                else if ($param->isDefaultValueAvailable()) {
                    $_args[] = $param->getDefaultValue();
                }
                else {
                    $_args[] = false;
                }
            }
        }

        // call build
        call_user_func_array(array($this, 'build'), $_args);

        // before
        $this->fire('before');

        // before render
        call_user_func(array($this, 'before'));

        // add any set data to the params load
        foreach ($this->_properties as $name) {
            $this->_params->set($name, $this->{$name});
        }

        // add our view to the vars
        $this->_params->self = $this;

        if ($this->_file !== false) {
            $dir = false;
            if ($this->_file{0} != '/') {
                $dir = ($this->_controller ? $this->_controller->getTemplateDir() : b::config()->getValue('project.templates')) . "/";
            }

            $this->setContent(b::render(array(
                'render' => $this->_render,
                'file' => $dir.$this->_file,
                'self' => $this,
                'vars' => $this->_params
            )));
        }
        else if ($this->_render) {
            $this->setContent(b::render(array(
                'render' => $this->_render,
                'string' => $this->_content,
                'self' => $this,
                'vars' => $this->_params
            )));
        }

        // after render
        call_user_func(array($this,'after'));

        // after
        $this->fire('after');

        return $this->getContent();
    }


    ////////////////////////////////////////////////////////////////////
    /// @brief render a template file and set controller content
    /// @see \bolt\render::render
    ///
    /// @param $file path to template file
    /// @param $vars array of variable
    /// @param $render name of render plugin
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function renderTemplate($file, $vars=array(), $render=false) {
        return $this->render(array(
            'file' => $file,
            'vars' => $vars,
            'render' => $render
        ));
    }

    ////////////////////////////////////////////////////////////////////
    /// @brief render a string and set as controller content
    ///
    ///
    /// @param $str string to render
    /// @param $vars array of variable
    /// @param $render name of render plugin
    /// @return self
    ////////////////////////////////////////////////////////////////////
    public function renderString($str, $vars=array(), $render=false) {
        return $this->render(array(
                'string' => $str,
                'vars' => $vars,
                'render' => $render
            ));
    }

}