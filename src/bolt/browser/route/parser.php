<?php

// start route namespace
namespace bolt\browser\route;
use \b;


/**
 * base router class
 * @abstract
 * @extends \bolt\event
 *
 */
abstract class parser extends \bolt\event {

    private $_path;
    private $_controller;
    private $_method = false;
    private $_action = false;
    private $_name = false;
    private $_weight = false;
    private $_validators = array();
    private $_params = array();
    private $_optional = array();
    private $_auth = array();
    private $_model = array();
    private $_response = false;
    private $_responsetype = 'html';

    // compiled
    protected $_compiled = false;

    /**
     * contrcut a new route parser
     *
     * @param $path route path
     * @param $class controller class
     * @return router object
     */
    final public function __construct($path, $controller) {
        $this->_name = uniqid('route');
        $this->_opath = $this->_path = $path;
        $this->_controller = $controller;
        $this->init();
    }

    public function setPath($path) {
        $this->_path = $path;
        return $this;
    }

    public function getOriginalPath() {
        return $this->_opath;
    }


    /**
     * MAGIC call a method
     *
     * @param $name
     * @param args
     * @return self
     */
    public function __call($name, $args) {
        if (substr($name,0,3) == 'set') {
            $name = strtolower(substr($name,3));
            if (property_exists($this, "_{$name}")) {
                $this->{"_$name"} = $args[0];
            }
        }
        else if (substr($name,0,3) == 'get') {
            $name = strtolower(substr($name,3));
            if (property_exists($this, "_{$name}")) {
                return $this->{"_$name"};
            }
        }
        else if (method_exists($this, $name)) {
            return call_user_func_array(array($this, $name), $args);
        }
        return $this;
    }

    public function set($name, $value) {
        if (property_exists($this, "_$name")) {
            $this->{"_$name"} = $value;
        }
        return $this;
    }

    /**
     * validate a route param
     *
     * @param $name (string|array) name of route param or array of validate settings
     * @param $regex regular expression to validate with
     * @return router object
     */
    public function validate($name, $regex=false) {
        if (is_array($name)) {
            foreach ($name as $n => $r) {
                $this->validate($n, $r);
            }
            return $this;
        }
        $this->_validators[$name] = trim($regex,' ()');
        return $this;
    }

    public function getControllerInstance() {
        $class = $this->_controller;
        $c = new $class();
        $c->setRoute($this);
        return $c;
    }

    /**
     * get a validator for a given route param name
     *
     * @param $name name of route param
     * @param $default default regexp
     * @return validator
     */
    public function getValidator($name, $default='[^/]+') {
        return (array_key_exists($name, $this->_validators) ? $this->_validators[$name] : $default);
    }

    public function hasValidator($name) {
        return array_key_exists($name, $this->_validators);
    }

    /**
     * check if the param is optional
     *
     * @param $name name of route param
     * @return bool
     */
    public function isOptional($name)  {
        return in_array($name, $this->_optional);
    }

    /**
     * set the name of the route
     *
     * @param $name
     * @return self
     */
    public function name($name) {
        $this->_name = $name;
        return $this;
    }

    /**
     * set the method of the route
     *
     * @param $method (array|string) array or csv of methods
     * @return self
     */
    public function method($method) {
        $this->_method = (is_array($method) ? $method : explode(',', $method));
        return $this;
    }

    /**
     * set optional params
     *
     * @param array $params
     * @return self
     */
    public function optional($params) {
        $this->_optional = array_merge($this->_optional, (is_array($params) ? $params : explode(",", $params)));
        return $this;
    }

    /**
     * set the route action
     *
     * @param $action
     * @return self
     */
    public function action($action) {
        $this->_action = $action;
        return $this;
    }

    /**
     * add before event
     *
     * @param $cb callback closure
     * @param $params args to pass to closure callback
     * @return self
     */
    public function before($cb, $params=array()) {
        $this->on("before", $cb, $params);
        return $this;
    }

    /**
     * add after event
     *
     * @param $cb callback closure
     * @param $params args to pass to closure callback
     * @return self
     */
    public function after($cb, $params=array()) {
        $this->on("after", $cb, $params);
        return $this;
    }

    public function auth($auth) {
        $this->_auth = $auth;
        return $this;
    }

    public function model($model) {
        $this->_model = (is_array($model) ? $model : explode(",", $model));
        return $this;
    }

    public function response($response) {
        $this->_response = (is_array($response) ? $response : explode(",", $response));
        return $this;
    }

    public function responseType($type) {
        $this->_responsetype = $type;
        return $this;
    }

    public function getResponseType() {
        return $this->_responsetype;
    }


    /**
     * match a string and method
     * @abstract
     *
     * @param $path string path
     * @return route object
     */
    abstract public function match($path);

    protected function compile() {

    }

}
