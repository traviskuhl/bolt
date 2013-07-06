<?php

namespace bolt\browser;
use \b;


/**
 * controller interface
 *
 * @interface
 */
interface iController {
    function render($args=array());
}

// depend on bolt browser
b::plug('controller', '\bolt\browser\controllerFactory');

/**
 * factory generator for controller class
 * @extends \bolt\plugin\singleton
 *
 */
class controllerFactory extends \bolt\plugin {

    // type is singleton
    // since this is really a plugin dispatch
    public static $TYPE = "factory";

    /**
     * generate a controller
     * @static
     *
     * @param $class controller class to generate
     * @return controller class
     */
    public static function factory($class='\bolt\browser\controller') {
        return new $class();
    }

}

/**
 * base controller class
 * @implements iController
 *
 */
class controller extends \bolt\event implements iController {

    // globals
    public static $globals = array();

    // some things we're going to need
    private $_bguid = false;
    private $_content = array('plain'=>"");
    private $_template = null;
    private $_render = 'handlebars';
    private $_properties = array();
    private $_layout = null;
    private $_parent = false;
    private $_hasRendered = false;
    private $_params;
    private $_data;
    private $_action = 'build';
    private $_templateBasePath = false;

    // static
    protected $_fromInit = false;

    /**
     * contruct a new view
     *
     * @param $params array of view params
     * @param $parent parent view
     * @return void
     */
    final public function __construct($params=array(), \bolt\browser\view $parent=null) {
        $this->_bguid = uniqid('b');
        $this->_params = b::bucket($params);
        $this->_parent = $parent;

        // any properties
        $ref = new \ReflectionClass($this);

        // globalize any properties already defined by this class
        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED) as $prop) {
            if (!$prop->isStatic()) {
                $this->_properties[] = $prop->getName();
            }
        }

        // init
        $this->_fromInit = $this->init();

        // globals
        self::$globals['request'] = b::request();
        self::$globals['response'] = b::response();
        self::$globals['settings'] = b::settings();

    }

    /**
     * get the unique id of view
     *
     * @return string guid
     */
    public function bGuid() {
        return $this->_bguid;
    }

    /**
     * called by __construct
     *
     * @return void
     */
    protected function init() {}


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
     * set the action
     *
     * @param $action name of action
     * @return self
     */
    public function setAction($action) {
        $this->_action = $action;
        return $this;
    }

    /**
     * get the action
     *
     * @return action string
     */
    public function getAction() {
        return $this->_action;
    }

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
        else if ($name == 'cookies' OR $name == 'cookie') {
            return b::cookie();
        }
        else if ($name == 'request') {
            return b::request();
        }
        else if ($name == 'response') {
            return b::response();
        }
        else if ($name == 'settings') {
            return b::settings();
        }
        else if (array_key_exists($name, $this->_properties)) {
            return $this->{$name};
        }
        else if (array_key_exists($name, self::$globals)) {
            return self::$globals[$name];
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
     * get view content
     *
     * @return view content
     */
    public function getContent($type=false, $render=false) {
        if ($type AND !array_key_exists($type, $this->_content)) {return false; }
        $resp = ($type ? $this->_content[$type] : $this->_content);
        if ($render AND is_object($resp) AND method_exists($resp, 'render')) {
            return $resp->render();
        }
        return $resp;
    }

    /**
     * set the view content
     *
     * @param $content view content
     * @return self
     */
    public function setContent($type, $content=false) {
        if (is_array($type)) {
            foreach ($type as $t => $c) {
                $this->setContent($t, $c);
            }
            return $this;
        }
        if ($type AND !$content) {
            $content = $type;
            $type = 'html';
        }
        $this->_content[$type] = $content;
        return $this;
    }


    /**
     * set the layout view
     *
     * @param $layout (string|\bolt\browser\view) path to template or view object
     * @return self
     */
    public function setLayout($layout) {
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
        return $this->_layout;
    }

    /**
     * set the view file
     *
     * @param $file path to file
     * @return self
     */
    public function setTemplate($file) {
        $this->_template = $file;
        return $this;
    }

    public function setBaseTemplatePath($path) {
        $this->_templateBasePath = $path;
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

    public function setData($data) {
        $this->_data = $data;
        return $this;
    }

    public function getData() {
        return $this->_data;
    }

    /**
     * render the given view
     *
     * @param $args array of argumnets
     * @return content of view
     */
    public function render($args=array()) {

        // globalize any args
        foreach ($args as $name => $value) {
            $this->{$name} = $value;
        }

        // add any set data to the params load
        foreach ($this->_properties as $name) {
            $this->_params->set($name, $this->{$name});
        }


        // before
        $this->fire('before');

        // before render
        call_user_func(array($this, 'before'));

        // build args
        $_args = array();

        // if action is a function
        // just call that
        $action = (is_string($this->getAction()) ? array($this, $this->getAction()) : $this->getAction());

        // reflect on build to see what to run
        $ref = (is_array($action) ? new \ReflectionMethod($action[0], $action[1]) : new \ReflectionFunction($action));

        // ref
        if ($ref->getNumberOfParameters() > 0) {
            foreach ($ref->getParameters() as $param) {
                $name = $param->getName();

                // is it a req/resp class
                if ($param->getClass() AND $param->getClass()->name == 'bolt\browser\request') {
                    $_args[] = b::request();
                }
                else if ($param->getClass() AND $param->getClass()->name == 'bolt\browser\response') {
                    $_args[] = b::response();
                }
                else if (array_key_exists($name, $args)) {
                    $_args[] = $args[$name];
                }
                else if ($this->_params->exists($name)) {
                    $_args[] = $this->_params->value($name);
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
        $resp = call_user_func_array($action, $_args);

        // is it a controller
        if (b::isInterfaceOf($resp, '\bolt\browser\iController')) {
            $i = 0; $run = $resp;
            while ($i++ <= 10) {

                // resp
                $last = $run->render($args);

                // not a controller we can stop
                if (!b::isInterfaceOf($resp, '\bolt\browser\iController') OR $last->bGuid() === $run->bGuid()) { break; }

                $run = $last;

            }

            // return run
            return $run;

        }
        else if (is_a($resp, '\bolt\browser\template') || is_array($resp) || is_string($resp)) {
            $this->setContent($resp);
        }

        // after render
        call_user_func(array($this,'after'));

        // after
        $this->fire('after');

        $this->_hasRendered = true;

        return $this;
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
    public function template($file, $vars=array(), $layout=null) {
        if ($layout === null) {
            $layout = $this->_layout;
        }

        // file exists
        if (!file_exists($file)) {
            $file = b::config('project')->value("templates")."/".ltrim($this->_templateBasePath,'/').$file;
        }

        // still no file
        // lets glob for any file extension that matches
        if (!file_exists($file) AND ($found = glob("{$file}.*")) != false) {
            $file = array_shift($found);
        }

        // get our final list of params
        $params = $this->_compileFinalParams($vars);

        // return a template object
        // to delay render until we need it
        return new template($this, $file, $layout, $params);

    }

    // render template
    public function renderTemplate($file, $vars=array(), $layout=false) {
        return $this->template($file, $vars, $layout)->render();
    }

    /**
     * render a string and set as controller content
     *
     *
     * @param $str string to render
     * @param $vars array of variable
     * @return self
     */
    public function renderString($str, $vars=array()) {
        $params = $this->_compileFinalParams($vars);
        return b::render(array(
            'string' => $str,
            'vars' => $params,
            'self' => $this
        ));
    }

    private function _compileFinalParams($vars) {
        $params = $this->_params;

        // globals
        foreach (self::$globals as $name => $value) {
            $params->set($name, $value);
        }

        // add any set data to the params load
        foreach ($this->_properties as $name) {
            $params->set($name, $this->{$name});
        }

        foreach ($vars as $name => $value) {
            $params->set($name, $value);
        }

        return $params;
    }

}

class template {
    private $_file;
    private $_vars;
    private $_layout = false;
    private $_parent;

    public function __construct($parent, $file, $layout, $vars) {
        $this->_parent = $parent;
        $this->_file = $file;
        $this->_layout = $layout;
        $this->_vars = $vars;
    }

    public function render() {

        $html = b::render(array(
            'file' => $this->_file,
            'self' => $this->_parent,
            'vars' => $this->_vars
        ));

        if ($this->_layout) {
            $this->_vars->set('yield', $html);
            $html = b::render(array(
                'file' => $this->_layout,
                'self' => $this->_parent,
                'vars' => $this->_vars
            ));
        }

        return $html;

    }

}