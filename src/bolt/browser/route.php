<?php

// namespace me
namespace bolt\browser {
use \b;

// plug route
b::plug(array(
    'route' => '\bolt\browser\route',
    'url' => 'route::url'
));


// route
class route extends \bolt\plugin\singleton {

    // type is singleton
    // since this is really a plugin dispatch
    public static $TYPE = "singleton";

    // routes
    private $_routes = array();

    // default
    public function _default() {
        if (count(func_get_args()) == 0 ) {
            return $this;
        }
        else {
            return call_user_func_array(array($this, 'register'), func_get_args());
        }
    }

    // get routes
    public function getRoutes() {
        return $this->_routes;
    }

    public function getRouteByName($name) {
        foreach ($this->_routes as $route) {
            if ($route->getName() == $name) {
                return $route;
            }
        }
        return false;
    }

    // register
    public function register($paths, $view=false, $method='*') {

        // if paths is not an object
        if (!is_object($paths)) {
            $cl = b::config()->get('router', '\bolt\browser\route\token')->value;
            $paths = new $cl($paths, $view, $method);
        }

        // add to routes
        $this->_routes[] = $paths;


        return $paths;

    }

    public function loadClassRoutes() {
        $classes = array();

        // get the files we've loaded
        foreach (get_declared_classes() as $class) {
            if (strpos($class, '\\') === false) {continue;}   // ignore anything that's not namespaced
            $c = new \ReflectionClass($class);
            $p = $c->getParentClass();

            if (
                $c->isSubclassOf('bolt\browser\controller') AND
                (
                    ($c->hasProperty('routes') AND $c->getProperty('routes')->isStatic()) OR
                    ($c->hasMethod('getRoutes') AND $c->getMethod('getRoutes')->isStatic())
                )
            ) {
                $classes[] = $c;
            }

        }

        // register their routes
        foreach ($classes as $class) {
            $route = array(); $dao = array();


            if ($class->hasProperty('routes')) {
                $route = $class->getProperty('routes')->getValue();
            }
            else {
                $method = $class->getMethod('getRoutes');
                $route = call_user_func(array($method->class, $method->name));
            }

            // dao
            if ($class->hasProperty('dao') AND $class->getProperty("dao")->isStatic()) {
                $dao = $class->getProperty('dao')->getValue();
            }

            if (is_string($route)) {
                $this->register($route, $class->getName());
            }
            else if (is_array($route)) {
                if (isset($route['route']) ) {
                    $route = array($route);
                }
                foreach ($route as $item) {
                    $r = $this->register($item['route'], $class->getName());
                    if (count($dao)) {
                        $r->dao($dao);
                    }
                    foreach ($item as $name => $value) {
                        if ($name != 'route') {
                            call_user_func(array($r, $name), $value);
                        }
                    }
                }
            }

        }

        return $this;
    }

    // match
    public function match($path=false, $method=false) {

        // class
        $controller = false;
        $params = array();

        // loop through each route and
        // try to match it
        foreach ($this->_routes as $route) {
            if ($route->match($path, $method) !== false) {
                $controller = $route->getController();
                $params = $route->getParams();
                break;
            }
        }

        // no view is bad
        if (!$controller AND $path != '*') {
            return $this->match('*', $method);
        }

        if (!$controller) {
            return false;
        }

        b::log('[b::route] found route %s', array($controller));

        // return what we foudn
        return $route;

    }

    // url
    public function url($name, $data=array(), $params=array(), $uri=false) {

        // no url
        if (!$uri) {
            $uri = URI;
        }

        // uri doesn't have a http://
        if (substr($uri,0,4) != 'http') {
            $uri = "http://$uri";
        }

        // not a sting
        if (!is_string($name)) { $name = (string)$name; }

        // no url
        if (!$this->getRouteByName($name)) {
            return rtrim(strtolower(b::addUrlParams(rtrim($uri,'/')."/".ltrim($name,'/'), $params)),'/');
        }

        // get our url
        $path = $this->getRouteByName($name)->getPath();

        foreach ($data as $k => $v) {
            $path = str_replace('{'.$k.'}', $v, $path );
        }

        // base url
        if (stripos($path, 'http') == false) {
            $path = rtrim($uri,'/') . "/" . trim($path,'/');
        }

        // return with params
        return rtrim(strtolower(b::addUrlParams($path, $params)),'/');

    }

}

} // end bold namespace

namespace bolt\browser\route {
use \b;


abstract class parser extends \bolt\event {

    private $_path;
    private $_controller;
    private $_method = '*';
    private $_action = false;
    private $_name = false;
    private $_weight = false;
    private $_validators = array();
    private $_params = array();
    private $_daos = array();

    final public function __construct($path, $controller, $method='*', $action=false) {
        $this->_path = $path;
        $this->_controller = $controller;
        $this->_method = $method;

        // before
        $this->on('before', array($this, 'initDaos'));
    }

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

    public function getValidator($name) {
        return (array_key_exists($name, $this->_validators) ? $this->_validators[$name] : '[^\/]+');
    }

    public function name($name) {
        $this->_name = $name;
        return $this;
    }

    public function method($method) {
        $this->_method = $method;
        return $this;
    }

    public function action($action) {
        $this->_action = $action;
        return $this;
    }

    public function dao($name, $class=false, $args=false) {
        if (is_array($name)) {
            if (is_string($name[0])) {
                $name = array($name);
            }
            foreach ($name as $dao) {
                $this->dao($dao[0], $dao[1], $dao[2]);
            }
            return $this;
        }


        $this->_daos[$name] = array('class' => $class, 'args' => $args);
        return $this;
    }

    public function before($cb, $params) {
        $this->on("before", $cb, $params);
        return $this;
    }

    public function after($cb, $params) {
        $this->on("after", $cb, $params);
        return $this;
    }

    // match a string
    abstract public function match($path, $method);

}

} // end route namespace