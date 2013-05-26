<?php

namespace bolt\browser;
use \b;

// plug view into bolt
b::plug('view', '\bolt\browser\viewFactory');

// add our render helper on run
b::on('run', function() {
    b::render()->handlebars->helper('view', function($template, $context, $args, $text) {
        $parts = explode(' ', $args);
        $class = trim(array_shift($parts), '"\'');
        $params = array();
        if (trim($text) AND is_array(json_decode(trim($text), true))) {
            $params += array_merge($params, json_decode(trim($text), true));
        }
        else if ($parts) {
            $params = json_decode(trim(implode(" ", $parts)), true);
        }
        $v =  b::view($class)->setParams($params);
        if ($context->get('controller')) {
            $v->setController($context->get('controller'));
        }
        return $v->render();
    });
});

/**
 * factory generator for view class
 * @extends \bolt\plugin\singleton
 *
 */
class viewFactory extends \bolt\plugin {

    // type is singleton
    // since this is really a plugin dispatch
    public static $TYPE = "factory";

    /**
     * generate a view
     * @static
     *
     * @param $class view class to generate
     * @return view class
     */
    public static function factory($class='\bolt\browser\view') {
        return new $class();
    }

}

interface iView {

}


/**
 * base view class
 * @implements iView
 *
 */
class view extends \bolt\event implements iView {

    // some things we're going to need
    private $_guid = false;
    private $_content = null;
    private $_template = null;
    private $_render = 'handlebars';
    private $_properties = array();
    private $_layout = null;
    private $_parent = false;
    private $_hasRendered = false;
    private $_params;

    // static
    protected $_fromInit = false;
    protected $layout = false;
    protected $template = false;

    /**
     * contruct a new view
     *
     * @param $params array of view params
     * @param $parent parent view
     * @return void
     */
    final public function __construct($params=array(), \bolt\browser\view $parent=null) {
        $this->_guid = uniqid();
        $this->_params = b::bucket($params);
        $this->_parent = $parent;

        // check if a layout property is set
        if ($this->layout) {
            $this->setLayout($this->layout);
        }
        if ($this->template) {
            $this->setTemplate($this->template);
        }

        // any properties
        $ref = new \ReflectionClass($this);

        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED) as $prop) {
            if (!$prop->isStatic()) {
                $this->_properties[] = $prop->getName();
            }
        }

        // init
        $this->_fromInit = $this->init();

    }

    /**
     * called by __construct
     *
     * @return void
     */
    protected function init() {}

    /**
     * called by render to set content
     *
     * @return void
     */
    protected function build() {}

    /**
     * called by render before build
     *
     * @return void
     */
    protected function before() {}

    /**
     * called by render after build and render
     *
     * @return void
     */
    protected function after() {}

    /**
     * set the parent view
     *
     * @param \bolt\browser\view $parent
     * @return self
     */
    public function setParent(\bolt\browser\view $parent) {
        $this->_parent = $parent;
        return $this;
    }

    /**
     * get the parent view
     *
     * @return parent view
     */
    public function getParent() {
        return $this->_parent;
    }


    /**
     * MAGIC get a param from params bucket
     * @see self::getParam
     *
     * @param $name
     * @return value
     */
    public function __get($name) {
        if ($name == 'params') {
            return $this->_params;
        }
        else if ($name == 'cookies') {
            return b::cookie();
        }
        else if (array_key_exists($name, $this->_properties)) {
            return $this->{$name};
        }
        return false;
    }

    /**
     * MAGIC set a param to params bucket
     * @see bucket::set
     *
     * @param $name
     * @param $value
     * @return void
     */
    public function __set($name, $value) {
        $this->_properties[] = $name;
        $this->{$name} = $value;
    }

    /**
     * get a param
     *
     * @param $name
     * @return value
     */
    public function getParam($name, $default=null) {
        if (!$name) {
            return b::bucket();
        }
        else if ($this->_params->exists($name)) {
            return $this->_params->get($name, $default);
        }
        else if (in_array($name, $this->_properties)) {
            return b::bucket($this->{$name});
        }
        else if ($this->_parent AND $this->_parent->exists($name)) {
            return $this->_parent->getParam($name, $default);
        }
        return ($default === null ? b::bucket() : $default);
    }

    public function getParamValue($name, $default=null) {
        $val = $this->getParam($name, $default);
        return ($val ?: $default);
    }

    /**
     * get params
     *
     * @return params bucket
     */
    public function getParams() {
        return $this->_params;
    }

    /**
     * set params
     *
     * @param $params
     * @return self
     */
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
        else if ($this->_parent AND $this->_parent->exists($name)) {
            return true;
        }
        return $this->_params->exists($name);
    }

    /**
     * get the unique id of view
     *
     * @return string guid
     */
    public function getGuid() {
        return $this->_guid;
    }

    /**
     * get view content
     *
     * @return view content
     */
    public function getContent() {
        return $this->_content;
    }

    /**
     * set the view content
     *
     * @param $content view content
     * @return self
     */
    public function setContent($content) {
        $this->_content = $content;
        return $this;
    }

    /**
     * set the layout view
     *
     * @param $layout (string|\bolt\browser\view) path to template or view object
     * @return self
     */
    public function setLayout($layout) {
        if (is_string($layout)) {
            $layout = b::view()
                        ->setTemplate($layout)
                        ->setParent($this);
        }
        if (!b::isInterfaceOf($layout, '\bolt\browser\iView')) {
            return $this;
        }
        $this->_layout = $layout;
        return $this;
    }

    /**
     * return layout view
     *
     * @return \bolt\browser\view
     */
    public function getLayout() {
        return $this->_layout;
    }

    /**
     * check if the controller has a layout view
     *
     * @return bool
     */
    public function hasLayout() {
        return is_object($this->_layout);
    }

    /**
     * set the view file
     *
     * @param $file path to file
     * @return self
     */
    public function setTemplate($file) {
        if ($file === false) {
            $this->_template = false;
        }
        else if (!file_exists($file)) {
            $file = b::config()->getValue("project.templates")."/".$file;
        }
        $this->_template = $file;
        return $this;
    }

    /**
     * get the view file
     *
     * @return view file
     */
    public function getTemplate() {
        return $this->_template;
    }

    /**
     * has a template
     *
     * @return bool
     */
    public function hasTemplate() {
        return $this->_template !== null;
    }


    /**
     * set renderer
     *
     * @param $name render name
     * @return self
     */
    public function setRenderer($name) {
        $this->_render = $name;
        return $this;
    }

    /**
     * get renderer
     *
     * @return renderer
     */
    public function getRenderer() {
        return $this->_render;
    }

    /**
     * has the view been rendered
     *
     * @return renderer
     */
    public function hasRendered() {
        return $this->_hasRendered;
    }

    /**
     * render the given view
     *
     * @param $args array of argumnets
     * @return content of view
     */
    final public function render($args=array()) {

        // globalize any args
        foreach ($args as $name => $value) {
            $this->{$name} = $value;
        }

        // before
        $this->fire('before');

        // before render
        call_user_func(array($this, 'before'));

        // build args
        $_args = array();

        // reflect on build to see what to run
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

        // add any set data to the params load
        foreach ($this->_properties as $name) {
            $this->_params->set($name, $this->{$name});
        }

        // add our view to the vars
        $this->_params->self = $this;


        if ($this->_template !== false AND $this->_content === null) {
            $this->setContent(b::render(array(
                'render' => $this->_render,
                'file' => $this->_template,
                'self' => $this,
                'vars' => $this->_params
            )));
        }
        else if ($this->_render AND $this->_content === null) {
            $this->setContent(b::render(array(
                'render' => $this->_render,
                'string' => $this->_content,
                'self' => $this,
                'vars' => $this->_params
            )));
        }

        // layout
        if ($this->hasLayout()) {
            $this->setContent(
                    $this->getLayout()
                        ->setParent($this)
                        ->setParams(array('child' => $this->getContent()))
                        ->render()
                );
        }

        // after render
        call_user_func(array($this,'after'));

        // after
        $this->fire('after');

        $this->_hasRendered = true;

        return $this->getContent();
    }


    /**
     * render a template file and set controller content
     * @see \bolt\render::render
     *
     * @param $file path to template file
     * @param $vars array of variable
     * @param $render name of render plugin
     * @return self
     */
    public function renderTemplate($file, $vars=array(), $render=false) {
        return b::render(array(
            'file' => $file,
            'vars' => $vars,
            'render' => $render,
            'self' => $this
        ));
    }

    /**
     * render a string and set as controller content
     *
     *
     * @param $str string to render
     * @param $vars array of variable
     * @param $render name of render plugin
     * @return self
     */
    public function renderString($str, $vars=array(), $render=false) {
        return b::render(array(
            'string' => $str,
            'vars' => $vars,
            'render' => $render,
            'self' => $this
        ));
    }

}