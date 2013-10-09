<?php

namespace bolt\browser;
use \b;


/**
 * controller interface
 *
 * @interface
 */
interface iController {

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
    private $_properties = array();
    private $_params;
    private $_parent;
    private $_responses = array();
    private $_request;
    private $_response;

    protected $_fromInit = false;
    protected $defaultResponseType = false;
    protected $responseType = false;
    protected $layout = false;


    /**
     * contruct a new view
     *
     * @param $config array configuration options
     *      request => request object
     *      response => response object
     *      route => base route
     *      params => start params
     * @return void
     */
    final public function __construct($config=array()) {
        $this->_bguid = uniqid('b');
        $this->_params = b::bucket(array_key_exists('params', $config) ? $config['params'] : array());

        // request and response
        $this->_response = (array_key_exists('response', $config) ? $config['response'] : b::browser()->getResponse() );
        $this->_request = (array_key_exists('request', $config) ? $config['request'] : b::browser()->getRequest() );
        $this->_route = (array_key_exists('route', $config) ? $config['route'] : false );

        // any properties
        $ref = new \ReflectionClass($this);

        // globalize any properties already defined by this class
        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            if (!$prop->isStatic()) {
                $this->_properties[] = $prop->getName();
            }
        }

        // init
        $this->_fromInit = $this->init();

    }


    public function setParent($parent) {
        $this->_parent = $parent;
        return $this;
    }

    public function getRequest() {
        return $this->_request;
    }

    public function getResponse() {
        return $this->_response;
    }

    public function getRoute() {
        return $this->_route;
    }

    public function setResponseType($type) {
        $this->responseType = $type;
        return $this;
    }


    public function getDefaultResponseType() {
        if ($this->defaultResponseType) {
            return $this->defaultResponseType;
        }
        else if ($this->_route) {
            return $this->_route->getResponseType();
        }
        return 'plain';
    }

    public function getResponseType() {
        if ($this->responseType) {
            return $this->responseType;
        }
        else if ($this->_route) {
            return $this->_route->getResponseType();
        }
        return 'plain';
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
            return $this->_request;
        }
        else if ($name == 'response') {
            return $this->_response;
        }
        else if ($name == 'settings') {
            return b::settings();
        }
        else if (array_key_exists($name, $this->_properties)) {
            return $this->{$name};
        }
        else if ($this->_parent AND property_exists($this->_parent, $name)) {
            return $this->_parent->{$name};
        }
        else if (array_key_exists($name, self::$globals)) {
            return self::$globals[$name];
        }
        return false;
    }

    public function model($model) {
        return b::model($model);
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
     * render the given view
     *
     * @param $args array of argumnets
     * @return content of view
     */
    public function invoke($action='build', $args=array()) {

        // init has something for us
        if ($this->_fromInit AND b::isInterfaceOf($this->_fromInit, '\bolt\browser\iController')) {
            return $this->_fromInit;
        }

        // if this action doesn't exist as a method we stop
        if (!method_exists($this, $action)) {
            return $this;
        }

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

        // reflect on build to see what to run
        $ref = new \ReflectionMethod($this, $action);

        // ref
        if ($ref->getNumberOfParameters() > 0) {
            foreach ($ref->getParameters() as $param) {
                $name = $param->getName();


                // is it a req/resp class
                if ($param->getClass() AND $param->getClass()->name == 'bolt\browser\request') {
                    $_args[] = $this->_request;
                }
                else if ($param->getClass() AND $param->getClass()->name == 'bolt\browser\response') {
                    $_args[] = $this->_response;
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
        $resp = call_user_func_array(array($this, $action), $_args);

        // if
        if (is_string($resp)) {
            $this->response($resp);
        }

        // response is a view
        if (b::isInterfaceOf($resp, '\bolt\browser\iView')) {
            $this->response($resp);
        }

        call_user_func(array($this, 'after'));

        // controller we give right back
        if (b::isInterfaceOf($resp, '\bolt\browser\iController') AND $resp->bGuid() != $this->_bguid) {
            return $resp;
        }

        // done
        return $this;

    }

    public function getResponseByType($type) {
        $resp = (array_key_exists($type, $this->_responses) ? $this->_responses[$type] : false);
        if (is_object($resp)) {
            $resp->setStatus( $this->_response->getStatus() );
        }
        return $resp;
    }

    public function responses($types) {
        foreach ($types as $t => $c) {
            $this->response($t, $c);
        }
        return $this;
    }

    public function response($type, $content=false) {
        $resp = false;

        // is it already a repsonse
        if (b::isInterfaceOf($type, '\bolt\browser\iResponse')) {
            $resp = $type;
        }

        // we have a type, no content and type isn't a response
        else if ($type AND $content === false) {
            $resp = \bolt\browser\response::initByType($this->getDefaultResponseType());
            $content = $type;
        }

        // use type
        else {
            $resp = \bolt\browser\response::initByType($type);
        }

        $resp->setParent($this->_response);

        // set our content
        $resp->setContent($content);

        // set the response
        $this->_responses[$resp::TYPE] = $resp;

        // return our response
        return $resp;

    }

    public function module($class) {
        if (array_key_exists($class, \bolt\browser\controller\module::$_modules)) {
            $class = \bolt\browser\controller\module::$_modules[$class];
        }
        $mod = new $class;
        $mod->setParent($this);
        return $mod;
    }

    public function renderModule($class, $vars=array()) {
        $m = $this->module($class);

        // get our final list of params
        $params = $this->_compileFinalParams($vars);

        return $m('build', $params);

    }

    public function controller($class) {
        $c = new $class();
        $c->setParent($this);
        return $c;
    }


    /**
     * render a view file and set controller content
     *
     * @param $file path to template file
     * @param $vars array of variable
     * @param $render name of render plugin
     * @return self
     */
    public function view($file, $vars=array(), $layout=null) {
        if ($layout === null) {
            $layout = $this->layout;
        }

        // get our final list of params
        $params = $this->_compileFinalParams($vars);


        // return a template object
        // to delay render until we need it
        return new view($this, $file, $layout, $params);

    }

    // render template
    public function renderView($file, $vars=array(), $layout=false) {

        // if it's a view
        if (b::isInterfaceOf($file, '\bolt\browser\iView')) {
            if ($vars) { $file->setVars($vars); }
            if ($layout) {$file->setLayoutFile($layout); }
            return $file->render();
        }

        // the view
        return $this->view($file, $vars, $layout)->render();
    }

    /**
     * render a string and set as controller content
     *
     *
     * @param $str string to render
     * @param $vars array of variable
     * @return self
     */
    public function renderString($str, $vars=array(), $renderer=false) {
        $params = $this->_compileFinalParams($vars);
        return b::render(array(
            'string' => $str,
            'vars' => $params,
            'self' => $this,
            'renderer' => $renderer
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
