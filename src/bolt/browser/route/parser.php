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
    private $_daos = array();

    /**
     * contrcut a new route parser
     *
     * @param $path route path
     * @param $class controller class
     * @return router object
     */
    final public function __construct($path, $controller) {
        $this->_path = $path;
        $this->_controller = $controller;

        // before
        $this->on('before', array($this, 'initDaos'));
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

    /**
     * initiate and DAOs defined by the route clas
     *
     * @return void
     */
    public function initDaos() {
        if (count($this->_daos) == 0) {return;}
        $resp = array();

        // loop through each item
        foreach ($this->_daos as $name => $model) {
            $o = b::dao($model['class']);
            $m = (isset($model['method']) ? $model['method'] : 'findById');
            $args = (isset($model['args']) ? $model['args'] : array());
            foreach ($args as $i => $value) {
                if ($value{0} == '$') {
                    $key = substr($value, 1);
                    if (array_key_exists($key, $this->_params)) {
                        $args[$i] = $this->_params[$key];
                    }
                }
            }
            $this->_params[$name] = call_user_func_array(array($o, $m), $args);
        }

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


    /**
     * get a validator for a given route param name
     *
     * @param $name name of route param
     * @param $default default regexp
     * @return validator
     */
    public function getValidator($name, $default='[^\/]+') {
        return (array_key_exists($name, $this->_validators) ? $this->_validators[$name] : $default);
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
     * add a dao to initate on route start
     *
     * @param $name (array|string) array of dao or param name
     * @param $class dao class
     * @param $args arguments to pass to constructor
     * @return self
     */
    public function dao($name, $class=false, $args=false) {
        if (is_array($name)) {
            if (is_string($name[0])) {
                $name = array($name);
            }
            foreach ($name as $dao) {
                $args = (isset($dao[2]) ? $dao[2] : false);
                $this->dao($dao[0], $dao[1], $args);
            }
            return $this;
        }
        $this->_daos[$name] = array('class' => $class, 'args' => $args);
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

    /**
     * match a string and method
     * @abstract
     *
     * @param $path string path
     * @return route object
     */
    abstract public function match($path);

}
